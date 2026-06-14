<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesOrderService
{
    public function __construct(private ProductService $productService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = SalesOrder::query()->with('customer')->latest('id');
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['customer_id'])) {
            $q->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where('order_no', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($w) => $w->where('name', 'like', "%{$s}%"));
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    /**
     * 创建销售单 + 明细 + 自动减库存（不足则抛出异常）。
     *
     * @param  array{customer_name:?string,items:array<array{product_name:string,quantity:int,unit_price:string}>,notes:?string}  $data
     *
     * @throws RuntimeException 库存不足
     */
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data): SalesOrder {
            $customerId = null;
            if (! empty($data['customer_name'])) {
                $customerId = Customer::query()
                    ->where('name', 'like', '%'.$data['customer_name'].'%')
                    ->value('id');
            }

            $totalAmount = 0;
            $resolved = [];

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->where('name', 'like', '%'.$item['product_name'].'%')
                    ->first();
                if (! $product) {
                    throw new RuntimeException("没找到商品「{$item['product_name']}」");
                }
                $qty = (int) $item['quantity'];
                if ($product->stock_quantity < $qty) {
                    throw new RuntimeException(
                        "「{$product->name}」库存不足（当前 {$product->stock_quantity}{$product->unit}，需 {$qty}）"
                    );
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
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
                'completed_at' => now(),
            ]);

            foreach ($resolved as $r) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $r['product']->id,
                    'quantity' => $r['qty'],
                    'unit_price' => $r['unitPrice'],
                    'total_price' => $r['totalPrice'],
                ]);
                $this->productService->adjustStock(
                    $r['product'], -$r['qty'], 'sale',
                    "销售单 {$orderNo}", SalesOrder::class, $order->id
                );
            }

            return $order;
        });
    }

    public function updateStatus(SalesOrder $order, array $data): SalesOrder
    {
        if (! empty($data['status']) && $data['status'] === 'shipped' && ! $order->shipped_at) {
            $data['shipped_at'] = now();
        }
        if (! empty($data['status']) && $data['status'] === 'completed' && ! $order->completed_at) {
            $data['completed_at'] = now();
        }
        $order->update(array_filter($data, fn ($v) => $v !== null));

        return $order;
    }

    public function delete(SalesOrder $order): void
    {
        $order->items()->delete();
        $order->delete();
    }
}
