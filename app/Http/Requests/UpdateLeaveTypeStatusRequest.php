<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeStatusRequest extends FormRequest
{
    /**
     * ACT-10: activate/deactivate toggle. Deactivating hides the type from
     * the new request form but does not touch existing requests (13.6).
     */
    public function authorize(): bool
    {
        return $this->user()?->isHrAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
