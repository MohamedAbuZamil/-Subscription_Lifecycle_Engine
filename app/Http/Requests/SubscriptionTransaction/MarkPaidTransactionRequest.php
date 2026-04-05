<?php

namespace App\Http\Requests\SubscriptionTransaction;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at'                 => ['sometimes', 'nullable', 'date'],
            'provider_transaction_id' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
