<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiAssistantController extends Controller
{
    public function __construct(private AiService $ai) {}

    /**
     * 进销存/CRM AI 助手入口：LLM 解析意图 → 路由到对应业务方法 → 返回 {reply,intent}。
     * 公开接口（单租户 app2，无需登录）。
     */
    public function message(Request $request): JsonResponse
    {
        $text = trim((string) $request->input('text', ''));
        if ($text === '') {
            return response()->json(['reply' => '请输入内容。', 'intent' => 'other']);
        }

        $p = $this->ai->parseIntent($text);
        $intent = $p['intent'] ?? 'other';

        $reply = match ($intent) {
            // ── 查询 ──────────────────────────────────────────────────────────
            'product_query' => $this->productQuery($p['keyword'] ?? null),
            'purchase_query' => $this->purchaseQuery($p['keyword'] ?? null),
            'sales_query' => $this->salesQuery($p['keyword'] ?? null),
            'inventory_query' => $this->inventoryQuery($p['keyword'] ?? null),
            'supplier_query' => $this->supplierQuery($p['keyword'] ?? null),
            'category_query' => $this->categoryQuery(),
            'customer_query' => $this->customerQuery($p['keyword'] ?? null),
            'lead_query' => $this->leadQuery($p['keyword'] ?? null),
            'opportunity_query' => $this->opportunityQuery($p['keyword'] ?? null),
            'activity_query' => $this->activityQuery($p['keyword'] ?? null),
            'contact_query' => $this->contactQuery($p['keyword'] ?? null),
            'overview_query' => $this->overview(),
            // ── 新增 ──────────────────────────────────────────────────────────
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
            // ── 修改 ──────────────────────────────────────────────────────────
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
            // ── 删除 ──────────────────────────────────────────────────────────
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
            default => $p['reply'] ?: '我可以帮你管理商品、订单、库存、供应商、客户、线索、商机、跟进记录，或查看整体概况。',
        };

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
        $q = Product::query()->with('category')->orderBy('stock_quantity');
        if ($kw) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$kw}%")->orWhere('sku', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(15)->get();
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
        $product = Product::create([
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
        $product = Product::query()
            ->where('name', 'like', '%'.$u['keyword'].'%')
            ->orWhere('sku', 'like', '%'.$u['keyword'].'%')
            ->first();
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
        $product->update($fields);

        return "✅ 已更新商品「{$product->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function productDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪个商品。';
        }
        $product = Product::query()
            ->where('name', 'like', "%{$kw}%")
            ->orWhere('sku', 'like', "%{$kw}%")
            ->first();
        if (! $product) {
            return "没找到商品「{$kw}」。";
        }
        $name = $product->name;
        $product->delete();

        return "🗑️ 已删除商品：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：库存
    // ═══════════════════════════════════════════════════════════════════════════

    /** 查找商品，找不到则自动建档（进货/库存调整场景用）。 */
    private function findOrCreateProduct(string $name, float $unitPrice = 0): array
    {
        $product = Product::query()->where('name', 'like', '%'.$name.'%')->first();
        $created = false;
        if (! $product) {
            $product = Product::create([
                'name' => $name,
                'sku' => 'P'.time(),
                'unit' => '个',
                'sell_price' => $unitPrice,
                'cost_price' => $unitPrice,
                'stock_quantity' => 0,
                'min_stock' => 0,
                'status' => 'active',
            ]);
            $created = true;
        }

        return [$product, $created];
    }

    private function stockUpdate(?array $s): string
    {
        if (! $s || empty($s['product_name'])) {
            return '请指定商品名和库存数量，例如："把苹果库存改成100"。';
        }
        if (! isset($s['quantity']) || (int) $s['quantity'] < 0) {
            return '请告诉我「'.$s['product_name'].'」要调整到多少库存？例如："把'.$s['product_name'].'库存改成50"。';
        }
        [$product, $created] = $this->findOrCreateProduct($s['product_name']);
        $notice = $created ? "（「{$product->name}」为新商品，已自动建档）\n" : '';
        $before = $product->stock_quantity;
        $after = (int) $s['quantity'];
        $product->stock_quantity = $after;
        $product->save();
        InventoryLog::create([
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => $after - $before,
            'before_stock' => $before,
            'after_stock' => $after,
            'notes' => $s['notes'] ?? 'AI助手手动调整',
        ]);

        return "✅ {$notice}「{$product->name}」库存已从 {$before} 调整为 {$after}{$product->unit}。";
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
        $q = Supplier::query();
        if ($kw) {
            $q->where('name', 'like', "%{$kw}%")->orWhere('contact_person', 'like', "%{$kw}%");
        }
        $rows = $q->limit(15)->get();
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
        $supplier = Supplier::create([
            'name' => $d['name'],
            'contact_person' => $d['contact_person'] ?? null,
            'phone' => $d['phone'] ?? null,
            'email' => $d['email'] ?? null,
            'address' => $d['address'] ?? null,
            'notes' => $d['notes'] ?? null,
            'status' => 'active',
        ]);

        return "✅ 已新增供应商：{$supplier->name}".($supplier->contact_person ? "（{$supplier->contact_person}）" : '').($supplier->phone ? " {$supplier->phone}" : '').'。';
    }

    private function supplierUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪家供应商，例如："把顺丰的联系人改为王总"。';
        }
        $supplier = Supplier::query()->where('name', 'like', '%'.$u['keyword'].'%')->first();
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
        $supplier->update($fields);

        return "✅ 已更新供应商「{$supplier->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function supplierDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪家供应商。';
        }
        $supplier = Supplier::query()->where('name', 'like', "%{$kw}%")->first();
        if (! $supplier) {
            return "没找到供应商「{$kw}」。";
        }
        $name = $supplier->name;
        $supplier->delete();

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
        $category = ProductCategory::create([
            'name' => $d['name'],
            'description' => $d['description'] ?? null,
        ]);

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
        $q = PurchaseOrder::query()->with('supplier')->latest('id');
        if ($kw) {
            $q->where('order_no', 'like', "%{$kw}%")
                ->orWhereHas('supplier', fn ($w) => $w->where('name', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(10)->get();
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
        $supplierId = null;
        if (! empty($d['supplier_name'])) {
            $supplierId = Supplier::query()->where('name', 'like', '%'.$d['supplier_name'].'%')->value('id');
        }

        return DB::transaction(function () use ($d, $supplierId): string {
            $totalAmount = 0;
            $resolved = [];
            $newProducts = [];
            foreach ($d['items'] as $item) {
                $qty = (int) ($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    return '请告诉我「'.$item['product_name'].'」进货多少数量？例如："进货苹果50个"。';
                }
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                [$product, $productCreated] = $this->findOrCreateProduct($item['product_name'], $unitPrice);
                if ($productCreated) {
                    $newProducts[] = $product->name;
                }
                $totalPrice = $qty * $unitPrice;
                $totalAmount += $totalPrice;
                $resolved[] = compact('product', 'qty', 'unitPrice', 'totalPrice');
            }
            $orderNo = 'PO'.date('YmdHis');
            $order = PurchaseOrder::create([
                'order_no' => $orderNo,
                'supplier_id' => $supplierId,
                'status' => 'received',
                'total_amount' => $totalAmount,
                'notes' => $d['notes'] ?? null,
                'ordered_at' => now(),
                'received_at' => now(),
            ]);
            $lines = [];
            foreach ($resolved as $r) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $r['product']->id,
                    'quantity' => $r['qty'],
                    'unit_price' => $r['unitPrice'],
                    'total_price' => $r['totalPrice'],
                ]);
                $before = $r['product']->stock_quantity;
                $after = $before + $r['qty'];
                $r['product']->stock_quantity = $after;
                $r['product']->save();
                InventoryLog::create([
                    'product_id' => $r['product']->id,
                    'type' => 'purchase',
                    'quantity' => $r['qty'],
                    'before_stock' => $before,
                    'after_stock' => $after,
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "进货单 {$orderNo}",
                ]);
                $lines[] = "  · {$r['product']->name} ×{$r['qty']}，单价 ¥{$r['unitPrice']}";
            }

            $notice = $newProducts ? '📝 新商品已自动建档：'.implode('、', $newProducts)."\n" : '';

            return "✅ {$notice}进货已录入（{$orderNo}）：\n".implode("\n", $lines)."\n合计 ¥{$totalAmount}，库存已更新。";
        });
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
        $fields = array_filter([
            'status' => $u['status'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"状态改为received"。';
        }
        $order->update($fields);

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
        $order->items()->delete();
        $order->delete();

        return "🗑️ 已删除采购单：{$no}（库存数量不会自动回退）";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 进销存：销售单
    // ═══════════════════════════════════════════════════════════════════════════

    private function salesQuery(?string $kw): string
    {
        $q = SalesOrder::query()->with('customer')->latest('id');
        if ($kw) {
            $q->where('order_no', 'like', "%{$kw}%")
                ->orWhereHas('customer', fn ($w) => $w->where('name', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(10)->get();
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
        $customerId = null;
        if (! empty($d['customer_name'])) {
            $customerId = Customer::query()->where('name', 'like', '%'.$d['customer_name'].'%')->value('id');
        }

        return DB::transaction(function () use ($d, $customerId): string {
            $totalAmount = 0;
            $resolved = [];
            foreach ($d['items'] as $item) {
                $product = Product::query()->where('name', 'like', '%'.$item['product_name'].'%')->first();
                if (! $product) {
                    return "没找到商品「{$item['product_name']}」，销售已取消。";
                }
                $qty = (int) ($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    return '请告诉我「'.$item['product_name'].'」卖了多少数量？例如："卖了苹果20个"。';
                }
                if ($product->stock_quantity < $qty) {
                    return "「{$product->name}」库存不足（当前 {$product->stock_quantity}{$product->unit}，需 {$qty}），销售已取消。";
                }
                $unitPrice = (float) ($item['unit_price'] ?? $product->sell_price ?? 0);
                $totalPrice = $qty * $unitPrice;
                $totalAmount += $totalPrice;
                $resolved[] = compact('product', 'qty', 'unitPrice', 'totalPrice');
            }
            $orderNo = 'SO'.date('YmdHis');
            $order = SalesOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $customerId,
                'status' => 'completed',
                'total_amount' => $totalAmount,
                'notes' => $d['notes'] ?? null,
                'ordered_at' => now(),
                'completed_at' => now(),
            ]);
            $lines = [];
            foreach ($resolved as $r) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $r['product']->id,
                    'quantity' => $r['qty'],
                    'unit_price' => $r['unitPrice'],
                    'total_price' => $r['totalPrice'],
                ]);
                $before = $r['product']->stock_quantity;
                $after = $before - $r['qty'];
                $r['product']->stock_quantity = $after;
                $r['product']->save();
                InventoryLog::create([
                    'product_id' => $r['product']->id,
                    'type' => 'sale',
                    'quantity' => -$r['qty'],
                    'before_stock' => $before,
                    'after_stock' => $after,
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "销售单 {$orderNo}",
                ]);
                $lines[] = "  · {$r['product']->name} ×{$r['qty']}，单价 ¥{$r['unitPrice']}";
            }

            return "✅ 销售已录入（{$orderNo}）：\n".implode("\n", $lines)."\n合计 ¥{$totalAmount}，库存已扣减。";
        });
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
        $fields = array_filter([
            'status' => $u['status'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么，例如"状态改为shipped"。';
        }
        if (! empty($fields['status']) && $fields['status'] === 'shipped' && ! $order->shipped_at) {
            $fields['shipped_at'] = now();
        }
        if (! empty($fields['status']) && $fields['status'] === 'completed' && ! $order->completed_at) {
            $fields['completed_at'] = now();
        }
        $order->update($fields);

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
        $order->items()->delete();
        $order->delete();

        return "🗑️ 已删除销售单：{$no}（库存数量不会自动回退）";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：客户
    // ═══════════════════════════════════════════════════════════════════════════

    private function customerQuery(?string $kw): string
    {
        $q = Customer::query()->latest('id');
        if ($kw) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$kw}%")->orWhere('company', 'like', "%{$kw}%")->orWhere('phone', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(15)->get();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关客户。" : '暂无客户数据。';
        }
        $lines = $rows->map(fn (Customer $c) => '· '.$c->name.($c->company ? "（{$c->company}）" : '').($c->phone ? " {$c->phone}" : '')." [{$c->status}]")->implode("\n");

        return "👥 客户（{$rows->count()} 条）：\n".$lines;
    }

    private function customerAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供客户姓名，例如："新增客户张三 13800138000 ABC公司"。';
        }
        $customer = Customer::create([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'company' => $d['company'] ?? null,
            'email' => $d['email'] ?? null,
            'status' => $d['status'] ?? 'active',
            'source' => $d['source'] ?? null,
            'notes' => $d['notes'] ?? null,
        ]);

        return "✅ 已新增客户：{$customer->name}".($customer->phone ? "（{$customer->phone}）" : '').($customer->company ? " {$customer->company}" : '').'。';
    }

    private function customerUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪位客户，例如："把张三手机改为138…"。';
        }
        $customer = Customer::query()
            ->where('name', 'like', '%'.$u['keyword'].'%')
            ->orWhere('company', 'like', '%'.$u['keyword'].'%')
            ->first();
        if (! $customer) {
            return "没找到客户「{$u['keyword']}」。";
        }
        $fields = array_filter([
            'name' => $u['name'] ?? null,
            'phone' => $u['phone'] ?? null,
            'email' => $u['email'] ?? null,
            'company' => $u['company'] ?? null,
            'status' => $u['status'] ?? null,
            'source' => $u['source'] ?? null,
            'notes' => $u['notes'] ?? null,
        ], fn ($v) => $v !== null);
        if (empty($fields)) {
            return '请说明要修改什么。';
        }
        $customer->update($fields);

        return "✅ 已更新客户「{$customer->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function customerDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪位客户。';
        }
        $customer = Customer::query()
            ->where('name', 'like', "%{$kw}%")
            ->orWhere('company', 'like', "%{$kw}%")
            ->first();
        if (! $customer) {
            return "没找到客户「{$kw}」。";
        }
        $name = $customer->name;
        $customer->delete();

        return "🗑️ 已删除客户：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：线索
    // ═══════════════════════════════════════════════════════════════════════════

    private function leadQuery(?string $kw): string
    {
        $q = Lead::query()->latest('id');
        if ($kw) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$kw}%")->orWhere('company', 'like', "%{$kw}%"));
        }
        $rows = $q->limit(15)->get();
        if ($rows->isEmpty()) {
            return '暂无线索数据。';
        }
        $lines = $rows->map(fn (Lead $l) => '· '.$l->name.($l->company ? "（{$l->company}）" : '')." [{$l->status}]")->implode("\n");

        return "🎯 线索（{$rows->count()} 条）：\n".$lines;
    }

    private function leadAdd(?array $d): string
    {
        if (! $d || empty($d['name'])) {
            return '请提供线索姓名，例如："录入线索李四 13900139000"。';
        }
        $lead = Lead::create([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'company' => $d['company'] ?? null,
            'email' => $d['email'] ?? null,
            'status' => $d['status'] ?? 'new',
            'source' => $d['source'] ?? null,
            'notes' => $d['notes'] ?? null,
        ]);

        return "✅ 已录入线索：{$lead->name}".($lead->phone ? "（{$lead->phone}）" : '').'。';
    }

    private function leadUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪条线索，例如："把李四线索状态改为qualified"。';
        }
        $lead = Lead::query()
            ->where('name', 'like', '%'.$u['keyword'].'%')
            ->orWhere('company', 'like', '%'.$u['keyword'].'%')
            ->first();
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
        $lead->update($fields);

        return "✅ 已更新线索「{$lead->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function leadDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪条线索。';
        }
        $lead = Lead::query()
            ->where('name', 'like', "%{$kw}%")
            ->orWhere('company', 'like', "%{$kw}%")
            ->first();
        if (! $lead) {
            return "没找到线索「{$kw}」。";
        }
        $name = $lead->name;
        $lead->delete();

        return "🗑️ 已删除线索：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：商机
    // ═══════════════════════════════════════════════════════════════════════════

    private function opportunityQuery(?string $kw): string
    {
        $q = Opportunity::query()->with('customer')->latest('id');
        if ($kw) {
            $q->where('title', 'like', "%{$kw}%");
        }
        $rows = $q->limit(10)->get();
        if ($rows->isEmpty()) {
            return '暂无商机数据。';
        }
        $lines = $rows->map(fn (Opportunity $o) => "· {$o->title} | ¥{$o->amount} | {$o->stage} | {$o->probability}%".($o->customer ? " — {$o->customer->name}" : ''))->implode("\n");

        return "💼 商机（{$rows->count()} 条）：\n".$lines;
    }

    private function opportunityAdd(?array $d): string
    {
        if (! $d || empty($d['title'])) {
            return '请提供商机标题，例如："新增商机：ABC公司采购项目 50万"。';
        }
        $customerId = null;
        if (! empty($d['customer_name'])) {
            $customerId = Customer::query()->where('name', 'like', '%'.$d['customer_name'].'%')->value('id');
        }
        $leadId = null;
        if (! empty($d['lead_name'])) {
            $leadId = Lead::query()->where('name', 'like', '%'.$d['lead_name'].'%')->value('id');
        }
        $opportunity = Opportunity::create([
            'title' => $d['title'],
            'amount' => $d['amount'] ?? 0,
            'stage' => $d['stage'] ?? 'prospect',
            'probability' => $d['probability'] ?? 10,
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'expected_close_date' => $d['expected_close_date'] ?? null,
            'notes' => $d['notes'] ?? null,
        ]);

        return "✅ 已新增商机：{$opportunity->title}".($opportunity->amount ? "（¥{$opportunity->amount}）" : '')."，阶段：{$opportunity->stage}。";
    }

    private function opportunityUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪个商机，例如："把ABC商机金额改为80万"。';
        }
        $opportunity = Opportunity::query()->where('title', 'like', '%'.$u['keyword'].'%')->first();
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
        $opportunity->update($fields);

        return "✅ 已更新商机「{$opportunity->title}」：".implode('、', array_keys($fields)).'。';
    }

    private function opportunityDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪个商机。';
        }
        $opportunity = Opportunity::query()->where('title', 'like', "%{$kw}%")->first();
        if (! $opportunity) {
            return "没找到商机「{$kw}」。";
        }
        $title = $opportunity->title;
        $opportunity->delete();

        return "🗑️ 已删除商机：{$title}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：跟进记录
    // ═══════════════════════════════════════════════════════════════════════════

    private function activityQuery(?string $kw): string
    {
        $q = Activity::query()->with(['customer', 'lead'])->latest('id');
        if ($kw) {
            $q->where(function ($w) use ($kw): void {
                $w->where('title', 'like', "%{$kw}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$kw}%"))
                    ->orWhereHas('lead', fn ($l) => $l->where('name', 'like', "%{$kw}%"));
            });
        }
        $rows = $q->limit(15)->get();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关跟进记录。" : '暂无跟进记录。';
        }
        $lines = $rows->map(function (Activity $a) {
            $related = $a->customer?->name ?? $a->lead?->name ?? '—';
            $done = $a->completed_at ? ' ✅' : '';

            return "· [{$a->type}] {$a->title} | {$related}".($a->scheduled_at ? ' '.$a->scheduled_at->format('m-d') : '').$done;
        })->implode("\n");

        return "📋 跟进记录（{$rows->count()} 条）：\n".$lines;
    }

    private function activityAdd(?array $d): string
    {
        if (! $d || empty($d['title'])) {
            return '请提供跟进标题，例如："记录对张三的电话跟进"。';
        }
        $customerId = null;
        if (! empty($d['customer_name'])) {
            $customerId = Customer::query()->where('name', 'like', '%'.$d['customer_name'].'%')->value('id');
        }
        $leadId = null;
        if (! empty($d['lead_name'])) {
            $leadId = Lead::query()->where('name', 'like', '%'.$d['lead_name'].'%')->value('id');
        }
        $opportunityId = null;
        if (! empty($d['opportunity_title'])) {
            $opportunityId = Opportunity::query()->where('title', 'like', '%'.$d['opportunity_title'].'%')->value('id');
        }
        $activity = Activity::create([
            'title' => $d['title'],
            'type' => $d['type'] ?? 'other',
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'opportunity_id' => $opportunityId,
            'description' => $d['description'] ?? null,
            'scheduled_at' => ! empty($d['scheduled_at']) ? $d['scheduled_at'] : now(),
        ]);
        $related = $customerId ? '客户：'.Customer::find($customerId)?->name
            : ($leadId ? '线索：'.Lead::find($leadId)?->name : '');

        return "✅ 已录入跟进：{$activity->title} [{$activity->type}]".($related ? "（{$related}）" : '').'。';
    }

    private function activityUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪条跟进记录，例如："标记张三电话跟进已完成"。';
        }
        $activity = Activity::query()
            ->where('title', 'like', '%'.$u['keyword'].'%')
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$u['keyword'].'%'))
            ->latest('id')->first();
        if (! $activity) {
            return "没找到「{$u['keyword']}」相关跟进记录。";
        }
        $fields = array_filter([
            'type' => $u['type'] ?? null,
            'description' => $u['description'] ?? null,
            'scheduled_at' => $u['scheduled_at'] ?? null,
        ], fn ($v) => $v !== null);
        if (! empty($u['mark_completed'])) {
            $fields['completed_at'] = now();
        }
        if (empty($fields)) {
            return '请说明要修改什么，例如"标记完成"或"备注改为…"。';
        }
        $activity->update($fields);
        $done = ! empty($u['mark_completed']) ? '，已标记完成 ✅' : '';

        return "✅ 已更新跟进「{$activity->title}」{$done}。";
    }

    private function activityDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪条跟进记录。';
        }
        $activity = Activity::query()->where('title', 'like', "%{$kw}%")->first();
        if (! $activity) {
            return "没找到「{$kw}」相关跟进记录。";
        }
        $title = $activity->title;
        $activity->delete();

        return "🗑️ 已删除跟进记录：{$title}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CRM：联系人
    // ═══════════════════════════════════════════════════════════════════════════

    private function contactQuery(?string $kw): string
    {
        $q = Contact::query()->with('customer');
        if ($kw) {
            $q->where(function ($w) use ($kw): void {
                $w->where('name', 'like', "%{$kw}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$kw}%")->orWhere('company', 'like', "%{$kw}%"));
            });
        }
        $rows = $q->limit(15)->get();
        if ($rows->isEmpty()) {
            return $kw ? "没找到「{$kw}」相关联系人。" : '暂无联系人数据。';
        }
        $lines = $rows->map(function (Contact $c) {
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
        $customer = Customer::query()
            ->where('name', 'like', '%'.$d['customer_name'].'%')
            ->orWhere('company', 'like', '%'.$d['customer_name'].'%')
            ->first();
        if (! $customer) {
            return "没找到客户「{$d['customer_name']}」，请先添加该客户。";
        }
        $contact = Contact::create([
            'customer_id' => $customer->id,
            'name' => $d['name'],
            'phone' => $d['phone'] ?? null,
            'email' => $d['email'] ?? null,
            'position' => $d['position'] ?? null,
            'is_primary' => (bool) ($d['is_primary'] ?? false),
        ]);

        return "✅ 已为「{$customer->name}」添加联系人：{$contact->name}".($contact->phone ? "（{$contact->phone}）" : '').($contact->position ? " {$contact->position}" : '').'。';
    }

    private function contactUpdate(?array $u): string
    {
        if (! $u || empty($u['keyword'])) {
            return '请告诉我要修改哪位联系人，例如："把王五的电话改为138…"。';
        }
        $contact = Contact::query()
            ->where('name', 'like', '%'.$u['keyword'].'%')
            ->when(! empty($u['customer_name']), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$u['customer_name'].'%')))
            ->first();
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
        $contact->update($fields);

        return "✅ 已更新联系人「{$contact->name}」：".implode('、', array_keys($fields)).'。';
    }

    private function contactDelete(?string $kw): string
    {
        if (! $kw) {
            return '请告诉我要删除哪位联系人。';
        }
        $contact = Contact::query()->where('name', 'like', "%{$kw}%")->first();
        if (! $contact) {
            return "没找到联系人「{$kw}」。";
        }
        $name = $contact->name;
        $contact->delete();

        return "🗑️ 已删除联系人：{$name}";
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 总览
    // ═══════════════════════════════════════════════════════════════════════════

    private function overview(): string
    {
        $lowStock = Product::query()->whereColumn('stock_quantity', '<=', 'min_stock')->count();

        return "📊 经营概况：\n"
            .'· 商品 '.Product::count()." 种".($lowStock > 0 ? "（⚠️{$lowStock} 种低库存）" : '')."\n"
            .'· 供应商 '.Supplier::count()." 家\n"
            .'· 客户 '.Customer::count()." 个\n"
            .'· 线索 '.Lead::count()." 条\n"
            .'· 商机 '.Opportunity::count()." 个\n"
            .'· 销售订单 '.SalesOrder::count()." 单\n"
            .'· 采购订单 '.PurchaseOrder::count().' 单';
    }
}
