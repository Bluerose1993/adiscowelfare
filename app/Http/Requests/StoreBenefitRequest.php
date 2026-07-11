<?php

namespace App\Http\Requests;

use App\Models\Benefit;
use Illuminate\Foundation\Http\FormRequest;

class StoreBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Benefit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'exists:staff,id'],
            'benefit_type_id' => ['required', 'exists:benefit_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'incident_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,approved,paid,rejected,cancelled'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
