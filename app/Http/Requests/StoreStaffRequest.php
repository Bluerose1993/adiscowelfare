<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrator') ?? false;
    }

    public function rules(): array
    {
        $staffId = $this->route('staff')?->id;

        return [
            'staff_id' => ['nullable', 'string', 'max:100', Rule::unique('staff', 'staff_id')->ignore($staffId)],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'employment_status' => ['nullable', 'string', 'max:100'],
            'date_joined' => ['nullable', 'date'],
            'association_joined_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'create_user' => ['nullable', 'boolean'],
            'temporary_password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
