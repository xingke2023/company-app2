<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Supplier::query()->latest();
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('contact_person', 'like', "%{$s}%"));
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create(array_merge(['status' => 'active'], $data));
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function findByKeyword(string $keyword): ?Supplier
    {
        return Supplier::query()->where('name', 'like', "%{$keyword}%")->first();
    }
}
