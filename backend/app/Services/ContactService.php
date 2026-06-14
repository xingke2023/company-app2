<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Contact::query()->with('customer:id,name,company')->latest();
        if (! empty($filters['customer_id'])) {
            $q->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Contact
    {
        return Contact::create($data);
    }

    public function update(Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact;
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }

    public function findByKeyword(string $keyword, ?string $customerKeyword = null): ?Contact
    {
        $q = Contact::query()->where('name', 'like', "%{$keyword}%");
        if ($customerKeyword) {
            $q->when(true, fn ($w) => $w->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$customerKeyword}%")));
        }

        return $q->first();
    }
}
