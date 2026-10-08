<?php

namespace App\Http\Requests;

use App\Enums\LeaveRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLeaveRequestRequest extends FormRequest
{
    /**
     * ACT-13 / FR-HIS-03: filters for the request list endpoints (employee
     * history, supervisor reports, HR company-wide view).
     *
     * Role-based scoping (employee sees own requests, supervisor sees direct
     * reports, HR sees all) is enforced in the controller/policy, never only
     * by these filters (FR-AUTH-02/03).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(LeaveRequestStatus::class)],
            'employee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'employee' => ['sometimes', 'string', 'max:255'],
            'department' => ['sometimes', 'string', 'max:255'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
