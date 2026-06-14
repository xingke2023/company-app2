<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssistantNote;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Services\ActivityService;
use App\Services\AiService;
use App\Services\ContactService;
use App\Services\CustomerService;
use App\Services\LeadService;
use App\Services\OpportunityService;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\SupplierService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiAssistantController extends Controller
{
    public function __construct(
        private AiService $ai,
        private CustomerService $customers,
        private LeadService $leads,
        private OpportunityService $opportunities,
        private ActivityService $activities,
        private ContactService $contacts,
        private ProductService $products,
        private PurchaseOrderService $purchaseOrders,
        private SalesOrderService $salesOrders,
        private SupplierService $suppliers,
    ) {}

    /**
     * 进销存/CRM AI 助手入口。支持文字 + 可选图片 Base64（拍照/相册）。
     * 公开接口（单租户 app2，无需登录）。
     */
    public function message(Request $request): JsonResponse
    {
        $text = trim((string) $request->input('text', ''));
        $imageBase64 = $request->input('image_base64') ?: null;

        if ($text === '' && ! $imageBase64) {
            return response()->json(['reply' => '请输入内容。', 'intent' => 'other']);
        }

        $p = $this->ai->parseIntent($text, $imageBase64);
        $intent = $p['intent'] ?? 'other';
        $reply = $this->dispatchIntent($intent, $p);

        return response()->json([
            'reply' => $reply,
            'intent' => $intent,
            'session_id' => $request->input('session_id'),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：商品
    // ═══════════════════════════════════════════════════════════════════════════

    private function productQuery(?string $kw): string
    {
        $rows = $this->products->list(['search' => $kw, 'per_page' => 15])->getCollection();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关商品。" : '暂无商品数据。';
        }
        $lines = $rows->map(function (Product $r) {
            $low = ($r->min_stock !== null && $r->stock_quantity <= $r->min_stock) ? ' ⚠️低库存' : '';

            return "· {$r->name}（{$r->sku}）库存 {$r->stock_quantity}{$r->unit} 售价 ¥{$r->sell_price}{$low}";
        })->implode("\n");

        return "📦 商品（{$rows->count()} 条）：\n".$lines;
    }

    private function productAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请告诉我商品名称，例如："新增商品：苹果 售价5元/斤"。';
        }
        $categoryId = null;
        if (! empty($d['category_name'])) {
            $categoryId = ProductCategory::query()->where('name', 'like', '%'.$d['category_name'].'%')->value('id');
        }
        $supplierId = null;
        if (! empty($d['supplier_name'])) {
            $supplierId = Supplier::query()->where('name', 'like', '%'.$d['supplier_name'].'%')->value('id');
        }
        $product = $this->products->create([
            'name' => $d['name'],
            'sku' => $d['sku'] ?? strtoupper('P'.time()),
            'unit' => $d['unit'] ?? '个',
            'sell_price' => $d['sell_price'] ?? 0,
            'cost_price' => $d['cost_price'] ?? 0,
            'stock_quantity' => (int) ($d['stock_quantity'] ?? 0),
            'min_stock' => isset($d['min_stock']) ? (int) $d['min_stock'] : 0,
            'status' => $d['status'] ?? 'active',
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
        ]);

        return "✅ 已新增商品：{$product->name}（{$product->sku}）售价 ¥{$product->sell_price}/{$product->unit}，初始库存 {$product->stock_quantity}。";
    }

    private function productUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪个商品，例如："把苹果售价改为6元"。';
        }
        $product = $this->products->findByKeyword($u['keyword']);
        if (! $product) {
            return "没找到商品「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'name' => $u['name'] ?? null,
            'sku' => $u['sku'] ?? null,
            'unit' => $u['unit'] ?? null,
            'sell_price' => $u['sell_price'] ?? null,
            'cost_price' => $u['cost_price'] ?? null,
            'min_stock' => isset($u['min_stock']) ? (int) $u['min_stock'] : null,
            'status' => $u['status'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"售价改为8元"或"状态改为下架"。';
        }
        $this->products->update($product, $fields);

        return "✅ 已更新商品「{$product->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function productDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪个商品。';
        }
        $product = $this->products->findByKeyword($kw);
        if (! $product) {
            return "没找到商品「{$kw}」。";
        }
        $name = $product->name;
        $this->products->delete($product);

        return "🗑️ 已删除商品：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：库存
    // ═══════════════════════════════════════════════════════════════════════════

    private function stockUpdate(?array $s): string
    {
        if (! $s || empty($s['product_name'])) {
            return '请指定商品名和库存数量，例如："把苹果库存改成100"。';
        }
        if (! isset($s['quantity']) || (int) $s['quantity'] < 0) {
            return '请告诉我「'.$s['product_name'].'」要调整到多少库存？例如："把'.$s['product_name'].'库存改成50"。';
        }
        [$product, $created] = $this->products->findOrCreate($s['product_name']);
        $notice = $created ? "（「{$product->name}」为新商品，已自动建档）\n" : '';
        $before = $product->stock_quantity;
        $this->products->setStock($product, (int) $s['quantity'], $s['notes'] ?? 'AI助手手动调整');

        return "✅ {$notice}「{$product->name}」库存已从 {$before} 调整为 {$s['quantity']}{$product->unit}。";
    }

    private function inventoryQuery(?string $kw): string
    {
        $q = InventoryLog::query()->with('product')->latest('id');
        if ($kw) {
            $q->whereHas('product', fn ($w) => $w->where('name', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(20)->get();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」的库存变动记录。" : '暂无库存变动记录。';
        }
        $lines = $rows->map(fn (InventoryLog $l) => '· '.$l->product?->name." [{$l->type}] "
            .($l->quantity >= 0 ? '+' : '').$l->quantity
            ." → {$l->after_stock} "
            .($l->created_at ? $l->created_at->format('m-d H:i') : '')
        )->implode("\n");

        return "📋 库存变动（{$rows->count()} 条）：\n".$lines;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：供应商
    // ═══════════════════════════════════════════════════════════════════════════

    private function supplierQuery(?string $kw): string
    {
        $rows = $this->suppliers->list(['search' => $kw, 'per_page' => 15])->getCollection();
        if ($rows->isEmpty()) {
            return '暂无供应商数据。';
        }
        $lines = $rows->map(fn (Supplier $s) => "· {$s->name}".($s->contact_person ? "（{$s->contact_person}）" : '').($s->phone ? " {$s->phone}" : ''))->implode("\n");

        return "🏭 供应商（{$rows->count()} 家）：\n".$lines;
    }

    private function supplierAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供供应商名称，例如："新增供应商顺丰物流 联系人李总 13800138000"。';
        }
        $supplier = $this->suppliers->create($d);

        return "✅ 已新增供应商：{$supplier->name}".($supplier->contact_person ? "（{$supplier->contact_person}）" : '').($supplier->phone ? " {$supplier->phone}" : '').'。';
    }

    private function supplierUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪家供应商，例如："把顺丰的联系人改为王总"。';
        }
        $supplier = $this->suppliers->findByKeyword($u['keyword']);
        if (! $supplier) {
            return "没找到供应商「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'name' => $u['name'] ?? null,
            'contact_person' => $u['contact_person'] ?? null,
            'phone' => $u['phone'] ?? null,
            'email' => $u['email'] ?? null,
            'status' => $u['status'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么。';
        }
        $this->suppliers->update($supplier, $fields);

        return "✅ 已更新供应商「{$supplier->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function supplierDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪家供应商。';
        }
        $supplier = $this->suppliers->findByKeyword($kw);
        if (! $supplier) {
            return "没找到供应商「{$kw}」。";
        }
        $name = $supplier->name;
        $this->suppliers->delete($supplier);

        return "🗑️ 已删除供应商：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：商品分类
    // ═══════════════════════════════════════════════════════════════════════════

    private function categoryQuery(): string
    {
        $rows = ProductCategory::query()->withCount('products')->get();
        if ($rows->isEmpty()) {
            return '暂无商品分类。';
        }
        $lines = $rows->map(fn (ProductCategory $c) => "· {$c->name}（{$c->products_count} 种商品）")->implode("\n");

        return "🗂️ 商品分类（{$rows->count()} 个）：\n".$lines;
    }

    private function categoryAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供分类名称，例如："新增商品分类：生鲜"。';
        }
        $category = ProductCategory::create(['name' => $d['name'], 'description' => $d['description'] ?? null]);

        return "✅ 已新增分类：{$category->name}";
    }

    private function categoryDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪个分类。';
        }
        $category = ProductCategory::query()->where('name', 'like', "%{$kw}%")->first();
        if (! $category) {
            return "没找到分类「{$kw}」。";
        }
        $name = $category->name;
        $category->delete();

        return "🗑️ 已删除分类：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：采购单
    // ═══════════════════════════════════════════════════════════════════════════

    private function purchaseQuery(?string $kw): string
    {
        $rows = $this->purchaseOrders->list(['search' => $kw, 'per_page' => 10])->getCollection();
        if ($rows->isEmpty()) {
            return '暂无采购订单。';
        }
        $lines = $rows->map(fn (PurchaseOrder $o) => "· {$o->order_no} | {$o->supplier?->name} | ¥{$o->total_amount} | {$o->status}")->implode("\n");

        return "🚚 采购订单（{$rows->count()} 条）：\n".$lines;
    }

    private function purchaseAdd(?array $d): string
    {
        if (! $d || empty($d['items'])) {
            return '请提供进货明细，例如："进货：苹果50个单价3元"。';
        }
        foreach ($d['items'] as $item) {
            if ((int) ($item['quantity'] ?? 0) <= 0) {
                return '请告诉我「'.$item['product_name'].'」进货多少数量？例如："进货苹果50个"。';
            }
        }
        try {
            ['order' => $order, 'newProducts' => $newProducts] = $this->purchaseOrders->create($d);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
        $notice = $newProducts ? '📝 新商品已自动建档：'.implode('、', $newProducts)."\n" : '';
        $lines = $order->items->map(fn ($i) => "  · {$i->product?->name} ×{$i->quantity}，单价 ¥{$i->unit_price}")->implode("\n");

        return "✅ {$notice}进货已录入（{$order->order_no}）：\n{$lines}\n合计 ¥{$order->total_amount}，库存已更新。";
    }

    private function purchaseUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪张采购单，例如："把采购单PO… 状态改为已收货"。';
        }
        $order = PurchaseOrder::query()->where('order_no', 'like', '%'.$u['keyword'].'%')->first();
        if (! $order) {
            return "没找到采购单「{$u['keyword']}」。";
        }
        $fields = array_filter(['status' => $u['status'] ?? null, 'notes' => $u['notes'] ?? null], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"状态改为received"。';
        }
        $this->purchaseOrders->updateStatus($order, $fields);

        return "✅ 采购单 {$order->order_no} 已更新：".implode('、', array_keys($fields)).'。';
    }

    private function purchaseDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪张采购单。';
        }
        $order = PurchaseOrder::query()->where('order_no', 'like', "%{$kw}%")->first();
        if (! $order) {
            return "没找到采购单「{$kw}」。";
        }
        $no = $order->order_no;
        $this->purchaseOrders->delete($order);

        return "🗑️ 已删除采购单：{$no}（库存数量不会自动回退）";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：销售单
    // ═══════════════════════════════════════════════════════════════════════════

    private function salesQuery(?string $kw): string
    {
        $rows = $this->salesOrders->list(['search' => $kw, 'per_page' => 10])->getCollection();
        if ($rows->isEmpty()) {
            return '暂无销售订单。';
        }
        $lines = $rows->map(fn (SalesOrder $o) => "· {$o->order_no} | {$o->customer?->name} | ¥{$o->total_amount} | {$o->status}")->implode("\n");

        return "🧾 销售订单（{$rows->count()} 条）：\n".$lines;
    }

    private function salesAdd(?array $d): string
    {
        if (! $d || empty($d['items'])) {
            return '请提供销售明细，例如："销售：张三买了苹果20个8元"。';
        }
        foreach ($d['items'] as $item) {
            if ((int) ($item['quantity'] ?? 0) <= 0) {
                return '请告诉我「'.$item['product_name'].'」卖了多少数量？例如："卖了苹果20个"。';
            }
        }
        try {
            $order = $this->salesOrders->create($d);
        } catch (RuntimeException $e) {
            return $e->getMessage().'，销售已取消。';
        }
        $order->load('items.product');
        $lines = $order->items->map(fn ($i) => "  · {$i->product?->name} ×{$i->quantity}，单价 ¥{$i->unit_price}")->implode("\n");

        return "✅ 销售已录入（{$order->order_no}）：\n{$lines}\n合计 ¥{$order->total_amount}，库存已扣减。";
    }

    private function salesUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪张销售单，例如："把销售单SO… 状态改为已发货"。';
        }
        $order = SalesOrder::query()->where('order_no', 'like', '%'.$u['keyword'].'%')->first();
        if (! $order) {
            return "没找到销售单「{$u['keyword']}」。";
        }
        $fields = array_filter(['status' => $u['status'] ?? null, 'notes' => $u['notes'] ?? null], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"状态改为shipped"。';
        }
        $this->salesOrders->updateStatus($order, $fields);

        return "✅ 销售单 {$order->order_no} 已更新：".implode('、', array_keys($fields)).'。';
    }

    private function salesDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪张销售单。';
        }
        $order = SalesOrder::query()
            ->where('order_no', 'like', "%{$kw}%")
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$kw}%"))
            ->first();
        if (! $order) {
            return "没找到销售单「{$kw}」。";
        }
        $no = $order->order_no;
        $this->salesOrders->delete($order);

        return "🗑️ 已删除销售单：{$no}（库存数量不会自动回退）";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：客户
    // ═══════════════════════════════════════════════════════════════════════════

    private function customerQuery(?string $kw): string
    {
        $rows = $this->customers->list(['search' => $kw, 'per_page' => 15])->getCollection();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关客户。" : '暂无客户数据。';
        }
        $lines = $rows->map(fn (Customer $c) => '· '.$this->customerLine($c))->implode("\n");

        return "👥 客户（{$rows->count()} 条）：\n".$lines;
    }

    private function customerProfileQuery(?array $f): string
    {
        if (! $f || empty(array_filter($f, fn ($v) => $v !== null && $v !== ''))) {
            return '请说明筛选条件，例如："查爱好高尔夫的客户"、"列出上海的高收入客户"。';
        }
        $rows = $this->customers->queryByProfile($f);
        $desc = [];
        foreach (['gender' => '性别', 'age_group' => '年龄段', 'city' => '城市', 'industry' => '行业', 'income_level' => '收入'] as $field => $label) {
            if (! empty($f[$field])) {
                $desc[] = "{$label}={$f[$field]}";
            }
        }
        if (! empty($f['hobby'])) {
            $desc[] = "爱好={$f['hobby']}";
        }
        if (! empty($f['tag'])) {
            $desc[] = "标签={$f['tag']}";
        }
        $cond = implode('、', $desc);
        if ($rows->isEmpty()) {
            return "没找到符合条件（{$cond}）的客户。";
        }
        $lines = $rows->map(fn (Customer $c) => '· '.$this->customerLine($c))->implode("\n");

        return "🎯 符合条件（{$cond}）的客户（{$rows->count()} 条）：\n".$lines;
    }

    private function customerLine(Customer $c): string
    {
        $profile = collect([$c->city, $c->industry, $c->income_level ? '收入'.$c->income_level : null, $c->hobbies ? '爱好:'.implode('/', $c->hobbies) : null, $c->tags ? '#'.implode(' #', $c->tags) : null])->filter()->implode(' ');

        return $c->name.($c->company ? "（{$c->company}）" : '').($c->phone ? " {$c->phone}" : '')." [{$c->status}]".($profile ? "\n    {$profile}" : '');
    }

    private function customerAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供客户姓名，例如："新增客户张三 13800138000 ABC公司"。';
        }
        $customer = $this->customers->create(array_filter([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'company' => $d['company'] ?? null,
            'email' => $d['email'] ?? null,
            'status' => $d['status'] ?? 'active',
            'source' => $d['source'] ?? null,
            'notes' => $d['notes'] ?? null,
            'gender' => $d['gender'] ?? null,
            'age_group' => $d['age_group'] ?? null,
            'birthday' => $d['birthday'] ?? null,
            'city' => $d['city'] ?? null,
            'industry' => $d['industry'] ?? null,
            'income_level' => $d['income_level'] ?? null,
            'hobbies' => ! empty($d['hobbies']) ? $d['hobbies'] : null,
            'tags' => ! empty($d['tags']) ? $d['tags'] : null,
        ], fn ($v) => $v !== null));

        return "✅ 已新增客户：{$customer->name}".($customer->phone ? "（{$customer->phone}）" : '').($customer->company ? " {$customer->company}" : '').'。';
    }

    private function customerUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪位客户，例如："把张三手机改为138…"。';
        }
        $customer = $this->customers->findByKeyword($u['keyword']);
        if (! $customer) {
            return "没找到客户「{$u['keyword']}」。";
        }
        $data = array_filter([
            'name' => $u['name'] ?? null,
            'phone' => $u['phone'] ?? null,
            'email' => $u['email'] ?? null,
            'company' => $u['company'] ?? null,
            'status' => $u['status'] ?? null,
            'source' => $u['source'] ?? null,
            'notes' => $u['notes'] ?? null,
            'gender' => $u['gender'] ?? null,
            'age_group' => $u['age_group'] ?? null,
            'birthday' => $u['birthday'] ?? null,
            'city' => $u['city'] ?? null,
            'industry' => $u['industry'] ?? null,
            'income_level' => $u['income_level'] ?? null,
            'hobbies' => ! empty($u['hobbies']) ? $u['hobbies'] : null,
            'hobbies_mode' => $u['hobbies_mode'] ?? 'add',
            'tags' => ! empty($u['tags']) ? $u['tags'] : null,
            'tags_mode' => $u['tags_mode'] ?? 'add',
        ], fn ($v) => $v !== null);

        if (count(array_diff_key($data, array_flip(['hobbies_mode', 'tags_mode']))) === 0) {
            return '请说明要修改什么。';
        }
        $this->customers->update($customer, $data);
        $changed = array_keys(array_diff_key($data, array_flip(['hobbies_mode', 'tags_mode'])));

        return "✅ 已更新客户「{$customer->name}」：".implode('、', $changed).'。';
    }

    private function customerDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪位客户。';
        }
        $customer = $this->customers->findByKeyword($kw);
        if (! $customer) {
            return "没找到客户「{$kw}」。";
        }
        $name = $customer->name;
        $this->customers->delete($customer);

        return "🗑️ 已删除客户：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：线索
    // ═══════════════════════════════════════════════════════════════════════════

    private function leadQuery(?string $kw): string
    {
        $rows = $this->leads->list(['search' => $kw, 'per_page' => 15])->getCollection();
        if ($rows->isEmpty()) {
            return '暂无线索数据。';
        }
        $lines = $rows->map(fn ($l) => '· '.$l->name.($l->company ? "（{$l->company}）" : '')." [{$l->status}]")->implode("\n");

        return "🎯 线索（{$rows->count()} 条）：\n".$lines;
    }

    private function leadAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供线索姓名，例如："录入线索李四 13900139000"。';
        }
        $lead = $this->leads->create(array_filter([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'company' => $d['company'] ?? null,
            'email' => $d['email'] ?? null,
            'status' => $d['status'] ?? 'new',
            'source' => $d['source'] ?? null,
            'notes' => $d['notes'] ?? null,
        ], fn ($v) => $v !== null));

        return "✅ 已录入线索：{$lead->name}".($lead->phone ? "（{$lead->phone}）" : '').'。';
    }

    private function leadUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪条线索，例如："把李四线索状态改为qualified"。';
        }
        $lead = $this->leads->findByKeyword($u['keyword']);
        if (! $lead) {
            return "没找到线索「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'phone' => $u['phone'] ?? null,
            'company' => $u['company'] ?? null,
            'email' => $u['email'] ?? null,
            'status' => $u['status'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么。';
        }
        $this->leads->update($lead, $fields);

        return "✅ 已更新线索「{$lead->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function leadDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪条线索。';
        }
        $lead = $this->leads->findByKeyword($kw);
        if (! $lead) {
            return "没找到线索「{$kw}」。";
        }
        $name = $lead->name;
        $this->leads->delete($lead);

        return "🗑️ 已删除线索：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：商机
    // ═══════════════════════════════════════════════════════════════════════════

    private function opportunityQuery(?string $kw): string
    {
        $rows = $this->opportunities->list(['per_page' => 10])->getCollection();
        if ($kw) {
            $rows = $rows->filter(fn ($o) => str_contains($o->title, $kw));
        }
        if ($rows->isEmpty()) {
            return '暂无商机数据。';
        }
        $lines = $rows->map(fn ($o) => "· {$o->title} | ¥{$o->amount} | {$o->stage} | {$o->probability}%".($o->customer ? " — {$o->customer->name}" : ''))->implode("\n");

        return "💼 商机（{$rows->count()} 条）：\n".$lines;
    }

    private function opportunityAdd(?array $d): string
    {
        if (! $d || empty($d['title'])) {
            return '请提供商机标题，例如："新增商机：ABC公司采购项目 50万"。';
        }
        $customerId = null;
        if (! empty($d['customer_name'])) {
            $customerId = $this->customers->findByKeyword($d['customer_name'])?->id;
        }
        $leadId = null;
        if (! empty($d['lead_name'])) {
            $leadId = $this->leads->findByKeyword($d['lead_name'])?->id;
        }
        $opportunity = $this->opportunities->create(array_filter([
            'title' => $d['title'],
            'amount' => $d['amount'] ?? 0,
            'stage' => $d['stage'] ?? 'prospect',
            'probability' => $d['probability'] ?? 10,
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'expected_close_date' => $d['expected_close_date'] ?? null,
            'notes' => $d['notes'] ?? null,
        ], fn ($v) => $v !== null));

        return "✅ 已新增商机：{$opportunity->title}".($opportunity->amount ? "（¥{$opportunity->amount}）" : '')."，阶段：{$opportunity->stage}。";
    }

    private function opportunityUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪个商机，例如："把ABC商机金额改为80万"。';
        }
        $opportunity = $this->opportunities->findByKeyword($u['keyword']);
        if (! $opportunity) {
            return "没找到商机「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'title' => $u['title'] ?? null,
            'amount' => $u['amount'] ?? null,
            'stage' => $u['stage'] ?? null,
            'probability' => isset($u['probability']) ? (int) $u['probability'] : null,
            'expected_close_date' => $u['expected_close_date'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么。';
        }
        $this->opportunities->update($opportunity, $fields);

        return "✅ 已更新商机「{$opportunity->title}」：".implode('、', array_keys($fields)).'。';
    }

    private function opportunityDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪个商机。';
        }
        $opportunity = $this->opportunities->findByKeyword($kw);
        if (! $opportunity) {
            return "没找到商机「{$kw}」。";
        }
        $title = $opportunity->title;
        $this->opportunities->delete($opportunity);

        return "🗑️ 已删除商机：{$title}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：跟进记录
    // ═══════════════════════════════════════════════════════════════════════════

    private function activityQuery(?string $kw): string
    {
        $filters = [];
        if ($kw) {
            $customerId = $this->customers->findByKeyword($kw)?->id;
            $leadId = $this->leads->findByKeyword($kw)?->id;
            if ($customerId) {
                $filters['customer_id'] = $customerId;
            } elseif ($leadId) {
                $filters['lead_id'] = $leadId;
            }
        }
        $rows = $this->activities->list(array_merge($filters, ['per_page' => 15]))->getCollection();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关跟进记录。" : '暂无跟进记录。';
        }
        $lines = $rows->map(function ($a) {
            $related = $a->customer?->name ?? $a->lead?->name ?? '—';
            $done = $a->completed_at ? ' ✅' : '';

            return "· [{$a->type}] {$a->title} | {$related}".($a->scheduled_at ? ' '.$a->scheduled_at->format('m-d') : '').$done;
        })->implode("\n");

        return "📋 跟进记录（{$rows->count()} 条）：\n".$lines;
    }

    /** 容错解析日期：LLM 可能返回"周五""明天"等非标准串，解析失败返回 null 而非击穿到数据库。 */
    private function safeDate(mixed $value): ?Carbon
    {
        if (empty($value) || ! is_string($value)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    private function activityAdd(?array $d): string
    {
        if (! $d || (empty($d['title']) && empty($d['type']) && empty($d['customer_name']) && empty($d['lead_name']))) {
            return '请提供跟进内容，例如："记录对张三的电话跟进"。';
        }
        $typeLabels = ['call' => '电话跟进', 'meeting' => '会议', 'email' => '邮件跟进', 'visit' => '拜访', 'other' => '跟进'];
        $type = $d['type'] ?? 'other';
        $title = ! empty($d['title']) ? $d['title'] : ($typeLabels[$type] ?? '跟进');
        $data = array_filter([
            'title' => $title,
            'type' => $type,
            'customer_id' => ! empty($d['customer_name']) ? $this->customers->findByKeyword($d['customer_name'])?->id : null,
            'lead_id' => ! empty($d['lead_name']) ? $this->leads->findByKeyword($d['lead_name'])?->id : null,
            'opportunity_id' => ! empty($d['opportunity_title']) ? $this->opportunities->findByKeyword($d['opportunity_title'])?->id : null,
            'description' => $d['description'] ?? null,
            'scheduled_at' => $this->safeDate($d['scheduled_at'] ?? null) ?? now(),
        ], fn ($v) => $v !== null);
        $activity = $this->activities->create($data);
        $related = $data['customer_id'] ? '客户：'.Customer::find($data['customer_id'])?->name : '';

        return "✅ 已录入跟进：{$activity->title} [{$activity->type}]".($related ? "（{$related}）" : '').'。';
    }

    private function activityUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪条跟进记录，例如："标记张三电话跟进已完成"。';
        }
        $activity = $this->activities->findByKeyword($u['keyword']);
        if (! $activity) {
            return "没找到「{$u['keyword']}」相关跟进记录。";
        }
        $fields = array_filter([
            'type' => $u['type'] ?? null,
            'description' => $u['description'] ?? null,
            'scheduled_at' => $this->safeDate($u['scheduled_at'] ?? null),
            'mark_completed' => $u['mark_completed'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"标记完成"或"备注改为…"。';
        }
        $this->activities->update($activity, $fields);
        $done = ! empty($u['mark_completed']) ? '，已标记完成 ✅' : '';

        return "✅ 已更新跟进「{$activity->title}」{$done}。";
    }

    private function activityDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪条跟进记录。';
        }
        $activity = $this->activities->findByKeyword($kw);
        if (! $activity) {
            return "没找到「{$kw}」相关跟进记录。";
        }
        $title = $activity->title;
        $this->activities->delete($activity);

        return "🗑️ 已删除跟进记录：{$title}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：联系人
    // ═══════════════════════════════════════════════════════════════════════════

    private function contactQuery(?string $kw): string
    {
        $rows = $this->contacts->list(['search' => $kw, 'per_page' => 15])->getCollection();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关联系人。" : '暂无联系人数据。';
        }
        $lines = $rows->map(function ($c) {
            $info = collect([$c->position, $c->phone, $c->email])->filter()->implode(' | ');

            return '· '.$c->name.'（'.$c->customer?->name.'）'.($info ? " — {$info}" : '').($c->is_primary ? ' ⭐' : '');
        })->implode("\n");

        return "👤 联系人（{$rows->count()} 条）：\n".$lines;
    }

    private function contactAdd(?array $d): string
    {
        if (! $d || empty($d['name']) || empty($d['customer_name'])) {
            return '请提供客户名和联系人姓名，例如："给ABC公司添加联系人王五 13800138000 采购总监"。';
        }
        $customer = $this->customers->findByKeyword($d['customer_name']);
        if (! $customer) {
            return "没找到客户「{$d['customer_name']}」，请先添加该客户。";
        }
        $contact = $this->contacts->create(array_filter([
            'customer_id' => $customer->id,
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'email' => $d['email'] ?? null,
            'position' => $d['position'] ?? null,
            'is_primary' => (bool) ($d['is_primary'] ?? false),
        ], fn ($v) => $v !== null));

        return "✅ 已为「{$customer->name}」添加联系人：{$contact->name}".($contact->phone ? "（{$contact->phone}）" : '').($contact->position ? " {$contact->position}" : '').'。';
    }

    private function contactUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪位联系人，例如："把王五的电话改为138…"。';
        }
        $contact = $this->contacts->findByKeyword($u['keyword'], $u['customer_name'] ?? null);
        if (! $contact) {
            return "没找到联系人「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'phone' => $u['phone'] ?? null,
            'email' => $u['email'] ?? null,
            'position' => $u['position'] ?? null,
            'is_primary' => isset($u['is_primary']) ? (bool) $u['is_primary'] : null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么。';
        }
        $this->contacts->update($contact, $fields);

        return "✅ 已更新联系人「{$contact->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function contactDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪位联系人。';
        }
        $contact = $this->contacts->findByKeyword($kw);
        if (! $contact) {
            return "没找到联系人「{$kw}」。";
        }
        $name = $contact->name;
        $this->contacts->delete($contact);

        return "🗑️ 已删除联系人：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 总览
    // ═══════════════════════════════════════════════════════════════════════════

    private function overview(): string
    {
        $lowStock = Product::query()->whereColumn('stock_quantity', '<=', 'min_stock')->count();

        return "📊 经营概况：\n"
            .'· 商品 '.Product::count().' 种'.($lowStock > 0 ? "（⚠️{$lowStock} 种低库存）" : '')."\n"
            .'· 供应商 '.Supplier::count()." 家\n"
            .'· 客户 '.Customer::count()." 个\n"
            .'· 采购订单 '.PurchaseOrder::count()." 单\n"
            .'· 销售订单 '.SalesOrder::count().' 单';
    }

    /**
     * 语音输入：接收音频文件 → Whisper 转文字 → 意图解析 → 返回回复。
     * 公开接口，无需登录。
     */
    public function voice(Request $request): JsonResponse
    {
        if (! config('ai.whisper_base_url')) {
            return response()->json([
                'reply' => '语音功能暂未配置，请联系管理员开通。',
                'intent' => 'other',
                'session_id' => $request->input('session_id'),
            ]);
        }

        $request->validate([
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a,webm,ogg', 'max:25600'],
        ]);

        $file = $request->file('audio');
        $filePath = $file->store('voice_temp', 'local');
        $fullPath = storage_path('app/'.$filePath);

        $transcribedText = $this->ai->transcribeVoice($fullPath);
        @unlink($fullPath);

        if (empty($transcribedText)) {
            return response()->json([
                'reply' => '语音识别失败，请重试或改用文字输入。',
                'intent' => 'other',
                'session_id' => $request->input('session_id'),
            ]);
        }

        $p = $this->ai->parseIntent($transcribedText);
        $intent = $p['intent'] ?? 'other';
        $reply = $this->dispatchIntent($intent, $p);

        return response()->json([
            'reply' => $reply,
            'intent' => $intent,
            'transcribed_text' => $transcribedText,
            'session_id' => $request->input('session_id'),
        ]);
    }

    /** 意图派发（复用于 message 和 voice 两个入口）。 */
    private function dispatchIntent(string $intent, array $p): string
    {
        return match ($intent) {
            'product_query' => $this->productQuery($p['keyword'] ?? null),
            'purchase_query' => $this->purchaseQuery($p['keyword'] ?? null),
            'sales_query' => $this->salesQuery($p['keyword'] ?? null),
            'inventory_query' => $this->inventoryQuery($p['keyword'] ?? null),
            'supplier_query' => $this->supplierQuery($p['keyword'] ?? null),
            'category_query' => $this->categoryQuery(),
            'customer_query' => $this->customerQuery($p['keyword'] ?? null),
            'customer_profile_query' => $this->customerProfileQuery($p['customer_filter'] ?? null),
            'lead_query' => $this->leadQuery($p['keyword'] ?? null),
            'opportunity_query' => $this->opportunityQuery($p['keyword'] ?? null),
            'activity_query' => $this->activityQuery($p['keyword'] ?? null),
            'contact_query' => $this->contactQuery($p['keyword'] ?? null),
            'overview_query' => $this->overview(),
            'product_add' => $this->productAdd($p['product'] ?? null),
            'purchase_add' => $this->purchaseAdd($p['purchase'] ?? null),
            'sales_add' => $this->salesAdd($p['sale'] ?? null),
            'supplier_add' => $this->supplierAdd($p['supplier'] ?? null),
            'category_add' => $this->categoryAdd($p['category'] ?? null),
            'customer_add' => $this->customerAdd($p['customer'] ?? null),
            'lead_add' => $this->leadAdd($p['lead'] ?? null),
            'opportunity_add' => $this->opportunityAdd($p['opportunity'] ?? null),
            'activity_add' => $this->activityAdd($p['activity'] ?? null),
            'contact_add' => $this->contactAdd($p['contact'] ?? null),
            'product_update' => $this->productUpdate($p['product_update'] ?? null),
            'purchase_update' => $this->purchaseUpdate($p['purchase_update'] ?? null),
            'sales_update' => $this->salesUpdate($p['sales_update'] ?? null),
            'stock_update' => $this->stockUpdate($p['stock'] ?? null),
            'supplier_update' => $this->supplierUpdate($p['supplier_update'] ?? null),
            'customer_update' => $this->customerUpdate($p['customer_update'] ?? null),
            'lead_update' => $this->leadUpdate($p['lead_update'] ?? null),
            'opportunity_update' => $this->opportunityUpdate($p['opportunity_update'] ?? null),
            'activity_update' => $this->activityUpdate($p['activity_update'] ?? null),
            'contact_update' => $this->contactUpdate($p['contact_update'] ?? null),
            'product_delete' => $this->productDelete($p['keyword'] ?? null),
            'purchase_delete' => $this->purchaseDelete($p['keyword'] ?? null),
            'sales_delete' => $this->salesDelete($p['keyword'] ?? null),
            'supplier_delete' => $this->supplierDelete($p['keyword'] ?? null),
            'category_delete' => $this->categoryDelete($p['keyword'] ?? null),
            'customer_delete' => $this->customerDelete($p['keyword'] ?? null),
            'lead_delete' => $this->leadDelete($p['keyword'] ?? null),
            'opportunity_delete' => $this->opportunityDelete($p['keyword'] ?? null),
            'activity_delete' => $this->activityDelete($p['keyword'] ?? null),
            'contact_delete' => $this->contactDelete($p['keyword'] ?? null),
            'assistant_note' => $this->saveAssistantNote($p['assistant_note'] ?? null),
            'ai_suggestion' => $this->saveAiSuggestion($p['reply'] ?? ''),
            default => $p['reply'] ?: '我可以帮你管理商品、订单、库存、供应商、客户、线索、商机、跟进记录，或查看整体概况。',
        };
    }

    private function saveAssistantNote(?array $d): string
    {
        if (! $d || empty($d['content'])) {
            return '请告诉我要记录的内容，例如："帮我记一下下午3点打电话给张三"。';
        }
        $type = ($d['type'] ?? 'note') === 'plan' ? 'plan' : 'note';
        $label = $type === 'plan' ? '计划' : '便签';
        AssistantNote::create(['type' => $type, 'content' => $d['content']]);

        return "📌 已记录到个人助理 · {$label}";
    }

    private function saveAiSuggestion(string $reply): string
    {
        if (empty($reply)) {
            return '没有收到建议内容，请重新描述您的问题。';
        }

        AssistantNote::create(['type' => 'ai_suggestion', 'content' => $reply]);

        return $reply."\n\n💡 已保存到个人助理 · AI建议";
    }
}
