<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBenefitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrator') ?? false;
    }

    public function rules(): array
    {
        $benefitTypeId = $this->route('benefit_type')?->id ?? $this->route('benefitType')?->id;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('benefit_types', 'name')->ignore($benefitTypeId)],
            'description' => ['nullable', 'string'],
            'default_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
