<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CancelLeaveRequestRequest extends FormRequest
{
    /**
     * ACT-04 / PRD section 6: the owner may cancel their own request, and
     * HR/Admin may cancel any request. The status rules are checked in
     * after() so the client receives a clear, specific message.
     */
    public function authorize(): bool
    {
        $leaveRequest = $this->route('leaveRequest');
        $user = $this->user();

        if ($leaveRequest === null || $user === null) {
            return false;
        }

        return $user->isHrAdmin() || $leaveRequest->user_id === $user->id;
    }

    /**
     * No request body is required to cancel.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * FR-REQ-06 / FR-BAL-03: the owner can only cancel while the request is
     * pending; HR can also cancel a fully approved request (the deducted
     * balance is restored in that case).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $leaveRequest = $this->route('leaveRequest');
                $user = $this->user();

                if ($leaveRequest === null || $user === null) {
                    return;
                }

                $allowed = $user->isHrAdmin()
                    ? $leaveRequest->isCancellable()
                    : $leaveRequest->isPending();

                if (! $allowed) {
                    $validator->errors()->add(
                        'status',
                        "This request can no longer be cancelled (current status: {$leaveRequest->status->label()}).",
                    );
                }
            },
        ];
    }
}
