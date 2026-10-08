<?php

namespace Tests\Unit;

use App\Support\WorkingDayCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class WorkingDayCalculatorTest extends TestCase
{
    public function test_it_counts_only_working_days_in_the_inclusive_range(): void
    {
        // Monday 2026-06-01 → Wednesday 2026-06-03.
        $this->assertSame(3, WorkingDayCalculator::count(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-03'),
        ));

        // Friday 2026-06-05 → Monday 2026-06-08: the weekend is not counted.
        $this->assertSame(2, WorkingDayCalculator::count(
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-08'),
        ));

        // A single weekday.
        $this->assertSame(1, WorkingDayCalculator::count(
            Carbon::parse('2026-06-02'),
            Carbon::parse('2026-06-02'),
        ));
    }

    public function test_it_returns_zero_for_weekend_only_or_inverted_ranges(): void
    {
        // Saturday 2026-06-06 → Sunday 2026-06-07.
        $this->assertSame(0, WorkingDayCalculator::count(
            Carbon::parse('2026-06-06'),
            Carbon::parse('2026-06-07'),
        ));

        $this->assertSame(0, WorkingDayCalculator::count(
            Carbon::parse('2026-06-03'),
            Carbon::parse('2026-06-01'),
        ));
    }
}
