<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * FR-REQ-02: the number of leave days a date range consumes.
 *
 * Leave is only consumed on working days, so Saturdays and Sundays are
 * excluded. This is the single source of truth shared by the submission
 * validation (StoreLeaveRequestRequest), the stored value and balance
 * deduction (LeaveRequestService), and the live preview on the request form
 * (resources/js/app.js must mirror this rule).
 */
class WorkingDayCalculator
{
    /**
     * Count the Monday–Friday days in the inclusive date range.
     *
     * Returns 0 when the range is empty (end before start) or contains only
     * weekend days, so callers can reject a request that would consume no
     * working day.
     */
    public static function count(Carbon $start, Carbon $end): int
    {
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        if ($last->lt($cursor)) {
            return 0;
        }

        $days = 0;

        while ($cursor->lte($last)) {
            if (! $cursor->isWeekend()) {
                $days++;
            }

            $cursor->addDay();
        }

        return $days;
    }
}
