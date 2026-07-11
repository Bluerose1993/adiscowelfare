<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewBenefitRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:under_review,approved,rejected,cancelled,paid'],
            'approved_amount' => ['nullable', 'required_if:status,approved', 'numeric', 'min:0.01', 'max:99999999.99'],
            'review_notes' => ['nullable', 'string'],
        ];
    }
}
