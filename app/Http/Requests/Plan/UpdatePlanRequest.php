<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string'],
            'trial_days'  => ['required', 'integer', 'min:0'],
            'is_active'   => ['required', 'boolean'],
        ];
    }
}
