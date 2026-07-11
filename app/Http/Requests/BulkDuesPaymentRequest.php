<?php

namespace App\Http\Requests;

use App\Models\DuesPayment;
use Illuminate\Foundation\Http\FormRequest;

class BulkDuesPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DuesPayment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'payments' => ['required', 'array'],
            'payments.*.staff_id' => ['required', 'exists:staff,id'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payments.*.payment_date' => ['nullable', 'date'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:150'],
        ];
    }
}
