<?php

namespace App\Http\Requests;

use App\Enums\ApprovalDecision;
use App\Enums\LeaveRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SupervisorDecisionRequest extends FormRequest
{
    /**
     * ACT-05 / ACT-06 / FR-AUTH-03: only the direct supervisor assigned to
     * the requesting employee may record the supervisor decision.
     *
     * The check is assignment-based on purpose: the PRD states the supervisor
     * acts only on requests from employees assigned to them. What happens when
     * the same person also holds another role is PRD open question #4.
     */
    public function authorize(): bool
    {
        $leaveRequest = $this->route('leaveRequest');
        $user = $this->user();

        return $user !== null
            && $leaveRequest !== null
            && $leaveRequest->user?->supervisor_id === $user->id;
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
     * FR-APR-02: rejection requires a comment.
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
     * FR-APR-01/05: a decision may only be recorded while the request is
     * waiting for the supervisor step.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $leaveRequest = $this->route('leaveRequest');

                if ($leaveRequest !== null && $leaveRequest->status !== LeaveRequestStatus::PendingSupervisor) {
                    $validator->errors()->add(
                        'status',
                        'This request is no longer waiting for the supervisor decision.',
                    );
                }
            },
        ];
    }
}
