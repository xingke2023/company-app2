<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Activity::query()->with(['customer:id,name', 'lead:id,name', 'opportunity:id,title', 'user:id,name'])->latest();
        foreach (['customer_id', 'lead_id', 'opportunity_id', 'type'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        if (isset($filters['completed'])) {
            $filters['completed']
                ? $q->whereNotNull('completed_at')
                : $q->whereNull('completed_at');
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Activity
    {
        return Activity::create($data);
    }

    public function update(Activity $activity, array $data): Activity
    {
        if (! empty($data['mark_completed'])) {
            $data['completed_at'] = now();
        }
        unset($data['mark_completed']);
        $activity->update(array_filter($data, fn ($v) => $v !== null));

        return $activity;
    }

    public function delete(Activity $activity): void
    {
        $activity->delete();
    }

    public function findByKeyword(string $keyword): ?Activity
    {
        return Activity::query()
            ->where('title', 'like', "%{$keyword}%")
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
            ->latest('id')->first();
    }
}
