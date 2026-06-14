<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Pagination\LengthAwarePaginator;

class OpportunityService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Opportunity::query()->with('customer:id,name')->latest();
        if (! empty($filters['stage'])) {
            $q->where('stage', $filters['stage']);
        }
        if (! empty($filters['customer_id'])) {
            $q->where('customer_id', $filters['customer_id']);
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Opportunity
    {
        return Opportunity::create($data);
    }

    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $opportunity->update($data);

        return $opportunity;
    }

    public function delete(Opportunity $opportunity): void
    {
        $opportunity->delete();
    }

    public function findByKeyword(string $keyword): ?Opportunity
    {
        return Opportunity::query()->where('title', 'like', "%{$keyword}%")->first();
    }
}
