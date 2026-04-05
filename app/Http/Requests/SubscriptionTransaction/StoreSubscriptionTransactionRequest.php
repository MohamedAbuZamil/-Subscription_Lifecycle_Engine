<?php

namespace App\Http\Requests\SubscriptionTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id'         => ['required', 'integer', 'exists:subscriptions,id'],
            'provider'                => ['sometimes', 'nullable', 'string'],
            'provider_transaction_id' => ['sometimes', 'nullable', 'string'],
            'metadata'                => ['sometimes', 'nullable', 'array'],
        ];
    }
}
