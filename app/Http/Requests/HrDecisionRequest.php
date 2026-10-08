<?php

namespace App\Http\Requests;

use App\Enums\ApprovalDecision;
use App\Enums\LeaveRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HrDecisionRequest extends FormRequest
{
    /**
     * ACT-07 / ACT-08: only HR/Admin may record the final decision.
     * The request must already have passed the supervisor step (checked in
     * after() for a clear status message).
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
            'decision' => ['required', Rule::enum(ApprovalDecision::class)],
            'comment' => [
                Rule::requiredIf(
                    fn (): bool => $this->input('decision') === ApprovalDecision::Rejected->value,
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * FR-APR-04: rejection requires a comment.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'A comment is required when rejecting a request.',
        ];
    }

    /**
     * FR-APR-03/05: HR can only decide after the supervisor approved, inside
     * the pending HR state. Balance is deducted on approval by the service
     * layer in the same transaction (FR-BAL-02).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $leaveRequest = $this->route('leaveRequest');

                if ($leaveRequest !== null && $leaveRequest->status !== LeaveRequestStatus::PendingHr) {
                    $validator->errors()->add(
                        'status',
                        'This request is not waiting for an HR decision.',
                    );
                }
            },
        ];
    }
}
