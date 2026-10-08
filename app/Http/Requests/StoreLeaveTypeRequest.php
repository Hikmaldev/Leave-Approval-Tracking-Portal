<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    /**
     * ACT-09: only HR/Admin can create leave types.
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
            'name' => ['required', 'string', 'max:255', Rule::unique('leave_types', 'name')],
            'requires_attachment' => ['required', 'boolean'],
            'default_annual_quota' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
