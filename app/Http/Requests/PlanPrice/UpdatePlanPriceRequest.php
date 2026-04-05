<?php

namespace App\Http\Requests\PlanPrice;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'           => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle'     => ['required', 'string', 'in:monthly,yearly'],
            'currency'          => ['required', 'string', 'size:3'],
            'price'             => ['required', 'numeric', 'gt:0'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'is_active'         => ['required', 'boolean'],
            'external_price_id' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
