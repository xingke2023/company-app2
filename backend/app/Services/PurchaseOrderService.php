<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private ProductService $productService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = PurchaseOrder::query()->with('supplier')->latest('id');
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where('order_no', 'like', "%{$s}%")
                ->orWhereHas('supplier', fn ($w) => $w->where('name', 'like', "%{$s}%"));
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    /**
     * 创建采购单 + 明细 + 自动加库存。
     *
     * @param  array{supplier_name:?string,items:array<array{product_name:string,quantity:int,unit_price:string}>,notes:?string}  $data
     * @return array{order:PurchaseOrder,newProducts:string[]}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $supplierId = null;
            if (! empty($data['supplier_name'])) {
                $supplierId = Supplier::query()
                    ->where('name', 'like', '%'.$data['supplier_name'].'%')
                    ->value('id');
            }

            $totalAmount = 0;
            $resolved = [];
            $newProducts = [];

            foreach ($data['items'] as $item) {
                [$product, $created] = $this->productService->findOrCreate(
                    $item['product_name'],
                    (float) ($item['unit_price'] ?? 0)
                );
                if ($created) {
                    $newProducts[] = $product->name;
                }
                $qty = (int) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);
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
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
                'received_at' => now(),
            ]);

            foreach ($resolved as $r) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $r['product']->id,
                    'quantity' => $r['qty'],
                    'unit_price' => $r['unitPrice'],
                    'total_price' => $r['totalPrice'],
                ]);
                $this->productService->adjustStock(
                    $r['product'], $r['qty'], 'purchase',
                    "进货单 {$orderNo}", PurchaseOrder::class, $order->id
                );
            }

            return ['order' => $order, 'newProducts' => $newProducts];
        });
    }

    public function updateStatus(PurchaseOrder $order, array $data): PurchaseOrder
    {
        $order->update(array_filter($data, fn ($v) => $v !== null));

        return $order;
    }

    public function delete(PurchaseOrder $order): void
    {
        $order->items()->delete();
        $order->delete();
    }
}
