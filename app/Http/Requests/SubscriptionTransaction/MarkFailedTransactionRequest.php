<?php

namespace App\Http\Requests\SubscriptionTransaction;

use Illuminate\Foundation\Http\FormRequest;

class MarkFailedTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'failure_reason' => ['required', 'string'],
            'failed_at'      => ['sometimes', 'nullable', 'date'],
        ];
    }
}
