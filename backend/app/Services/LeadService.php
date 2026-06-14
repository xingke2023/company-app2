<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Lead::query()->latest();
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('company', 'like', "%{$s}%"));
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead;
    }

    public function delete(Lead $lead): void
    {
        $lead->delete();
    }

    public function findByKeyword(string $keyword): ?Lead
    {
        return Lead::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('company', 'like', "%{$keyword}%")
            ->first();
    }
}
