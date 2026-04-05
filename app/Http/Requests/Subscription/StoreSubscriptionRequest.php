<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'       => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly'],
        ];
    }
}
