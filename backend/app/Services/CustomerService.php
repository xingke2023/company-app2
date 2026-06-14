<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Customer::query()->with('assignedUser:id,name')->withCount('salesOrders')->latest();

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('company', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"));
        }
        foreach (['status', 'gender', 'age_group', 'city', 'industry', 'income_level'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['hobby'])) {
            $hobbies = (array) $filters['hobby'];
            $q->where(fn ($w) => collect($hobbies)->each(fn ($h) => $w->orWhereJsonContains('hobbies', $h)));
        }
        if (! empty($filters['tag'])) {
            $tags = (array) $filters['tag'];
            $q->where(fn ($w) => collect($tags)->each(fn ($t) => $w->orWhereJsonContains('tags', $t)));
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        // 爱好/标签支持追加模式
        foreach (['hobbies', 'tags'] as $field) {
            if (isset($data[$field]) && ($data["{$field}_mode"] ?? 'add') === 'add') {
                $data[$field] = array_values(array_unique([
                    ...($customer->{$field} ?? []),
                    ...$data[$field],
                ]));
            }
            unset($data["{$field}_mode"]);
        }
        $customer->update($data);

        return $customer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    /** 按名称/公司模糊查找第一条。 */
    public function findByKeyword(string $keyword): ?Customer
    {
        return Customer::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('company', 'like', "%{$keyword}%")
            ->first();
    }

    /** 按画像特征筛选。 */
    public function queryByProfile(array $filters, int $limit = 20): Collection
    {
        $q = Customer::query()->latest('id');
        foreach (['gender', 'age_group', 'city', 'industry', 'income_level'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['hobby'])) {
            $q->whereJsonContains('hobbies', $filters['hobby']);
        }
        if (! empty($filters['tag'])) {
            $q->whereJsonContains('tags', $filters['tag']);
        }

        return $q->limit($limit)->get();
    }
}
