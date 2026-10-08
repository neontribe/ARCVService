<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsScottishAlmostStartDate extends AbstractSpecification
{
    /** @var Carbon $offsetDate */
    protected Carbon $offsetDate;

    /** @var int $schoolMonth */
    private int $schoolMonth;

    /**
     * IsScottishAlmostStartDate constructor.
     *
     * @param Carbon|null $offsetDate Usually today, unless a test has changed that.
     * @param int|null $schoolMonth What month school starts, defaults to config (usually 8 / August).
     */
    public function __construct(Carbon $offsetDate = null, int $schoolMonth = null)
    {
        $this->offsetDate = $offsetDate ?? Carbon::today()->startOfDay();
        $this->schoolMonth = $schoolMonth ?? (int) config('arc.scottish_school_month', 8);
    }

    /**
     * Tests an object and returns a boolean value
     *
     * @param Child $candidate
     * @return bool
     */
    public function isSatisfiedBy(Child $candidate): bool
    {
        $birthYear = $candidate->dob->year;
        $birthMonth = $candidate->dob->month;

        // In Scotland, children born March-December (month >= 3) start school in August at age 5 (birthYear + 5).
        // Children born January-February (month <= 2) start school in August at age 4.5 (birthYear + 4).
        $startYear = ($birthMonth <= 2) ? $birthYear + 4 : $birthYear + 5;

        // If the child is deferred, entry is delayed by 1 year.
        if ($candidate->deferred) {
            $startYear += 1;
        }

        $schoolStartDate = Carbon::createFromDate($startYear, $this->schoolMonth, 1)->startOfDay();

        // Month difference between offset date month and target school start date month.
        // Diff is 0 if evaluated during school start month, or 1 if evaluated the month before.
        $diffInMonths = $this->offsetDate->copy()->startOfMonth()->diffInMonths($schoolStartDate, false);

        return $diffInMonths >= 0 && $diffInMonths <= 1;
    }
}
