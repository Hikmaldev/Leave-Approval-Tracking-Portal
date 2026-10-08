<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachment;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveRequestRequest extends FormRequest
{
    use ValidatesAttachment;

    /**
     * ACT-02: every authenticated user may submit a request for themselves.
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
            'leave_type_id' => [
                'required',
                'integer',
                // Only active leave types can be used for new requests
                // (FR acceptance criteria 13.6).
                Rule::exists('leave_types', 'id')->where('is_active', true),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'attachment' => $this->attachmentRules(required: false),
        ];
    }

    /**
     * FR-REQ-04 (required attachment) and FR-REQ-05 (balance check).
     *
     * days_requested calculation and the actual storage/deduction happen in
     * the service layer; this hook only rejects clearly invalid submissions
     * before they reach it.
     *
     * NOTE: the day count below is inclusive calendar days. Confirm whether
     * weekends/holidays should be excluded before rollout.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $leaveType = LeaveType::find($this->integer('leave_type_id'));

                if ($leaveType === null) {
                    return;
                }

                if ($leaveType->requires_attachment && ! $this->hasFile('attachment')) {
                    $validator->errors()->add(
                        'attachment',
                        'This leave type requires an attachment (for example, a doctor\'s note).',
                    );
                }

                $days = (int) $this->date('start_date')->diffInDays($this->date('end_date')) + 1;

                $balance = LeaveBalance::query()
                    ->where('user_id', $this->user()->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $this->date('start_date')->year)
                    ->first();

                if ($balance !== null && $days > $balance->remainingDays()) {
                    $validator->errors()->add(
                        'end_date',
                        "This request asks for {$days} day(s), but only {$balance->remainingDays()} day(s) remain in this leave balance. Adjust the dates or contact HR.",
                    );
                }
            },
        ];
    }
}
