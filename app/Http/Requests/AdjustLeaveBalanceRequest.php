<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustLeaveBalanceRequest extends FormRequest
{
    /**
     * ACT-12 / FR-BAL-05: HR adjusts an employee balance, and the reason is
     * mandatory.
     *
     * At least one of quota / used must be provided (each becomes required
     * when the other is missing). Adjustments are audit-sensitive: the PRD
     * requires the reason to be logged, but the current Data Spec has no
     * place to persist it yet (flagged for review).
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
            'quota' => ['required_without:used', 'integer', 'min:0'],
            'used' => ['required_without:quota', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
