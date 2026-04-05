<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class PatchPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'        => ['sometimes', 'string', 'min:2', 'max:64', 'regex:/^[a-z0-9\-]+$/', 'unique:plans,code,' . $this->route('plan')->id],
            'name'        => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'trial_days'  => ['sometimes', 'integer', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
