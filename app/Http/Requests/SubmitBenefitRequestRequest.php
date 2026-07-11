<?php

namespace App\Http\Requests;

use App\Models\BenefitRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitBenefitRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BenefitRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'benefit_type_id' => ['required', Rule::exists('benefit_types', 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'requested_amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'incident_date' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }
}
