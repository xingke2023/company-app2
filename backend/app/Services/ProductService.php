<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Product::query()->with('category', 'supplier')->where('status', 'active');
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if (! empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }

        return $q->orderBy('stock_quantity')->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function findByKeyword(string $keyword): ?Product
    {
        return Product::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('sku', 'like', "%{$keyword}%")
            ->first();
    }

    /** 查找商品，找不到则自动建档。 */
    public function findOrCreate(string $name, float $unitPrice = 0): array
    {
        $product = Product::query()->where('name', 'like', "%{$name}%")->first();
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

    /** 调整库存并写入 InventoryLog。 */
    public function adjustStock(
        Product $product,
        int $delta,
        string $type,
        string $notes = '',
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        $before = $product->stock_quantity;
        $after = $before + $delta;
        $product->stock_quantity = $after;
        $product->save();

        InventoryLog::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $delta,
            'before_stock' => $before,
            'after_stock' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }

    /** 直接设置库存到指定数量。 */
    public function setStock(Product $product, int $quantity, string $notes = 'AI助手手动调整'): void
    {
        $delta = $quantity - $product->stock_quantity;
        $this->adjustStock($product, $delta, 'adjustment', $notes);
    }
}
