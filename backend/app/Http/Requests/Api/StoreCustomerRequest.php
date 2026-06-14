<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', 'in:individual,company'],
            'source' => ['nullable', 'in:web,referral,call,exhibition,other'],
            'status' => ['nullable', 'in:active,inactive,potential'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            // 客户画像
            'gender' => ['nullable', 'in:male,female,other'],
            'age_group' => ['nullable', 'in:18-25,26-35,36-45,46-55,55+'],
            'birthday' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'income_level' => ['nullable', 'in:low,medium,high,ultra-high'],
            'hobbies' => ['nullable', 'array'],
            'hobbies.*' => ['string', 'max:50'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
