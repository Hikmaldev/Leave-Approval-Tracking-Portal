<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    /**
     * ACT-09 (edit path): only HR/Admin can update leave types. Changes apply
     * to new requests only; submitted requests keep their original config
     * (PRD core business rule 5).
     */
    public function authorize(): bool
    {
        return $this->user()?->isHrAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_types', 'name')->ignore($this->route('leaveType')),
            ],
            'requires_attachment' => ['required', 'boolean'],
            'default_annual_quota' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
