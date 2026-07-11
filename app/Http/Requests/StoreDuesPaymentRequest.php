<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDuesPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\DuesPayment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'exists:staff,id'],
            'payment_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_month' => 'month',
            'payment_year' => 'year',
        ];
    }
}
