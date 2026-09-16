<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsAlmostStartDate extends AbstractSpecification
{
    protected Carbon $offsetDate;

    private int $yearsAhead;

    private int $offsetMonth;

    /**
     * IsAlmostStartDate constructor.
     *
     * @param Carbon $offsetDate Usually today, unless a test had changed that.
     * @param int $yearsAhead How many years away we want to look
     * @param int $offsetMonth What month the event will be, usually configured to 9
     */
    public function __construct(Carbon $offsetDate, int $yearsAhead, int $offsetMonth)
    {
        $this->offsetMonth = $offsetMonth;
        $this->yearsAhead = $yearsAhead;
        $this->offsetDate = $offsetDate;
    }

    /**
     * Tests an object and returns a boolean value
     *
     * @param Child $candidate
     * @return  Boolean
     */
    public function isSatisfiedBy(Child $candidate): bool
    {
        // Generate the date of the event in question
        $targetDate = $candidate->calcFutureMonthYear($this->yearsAhead, $this->offsetMonth);

        // Month difference between offset date month and target start date month.
        // Diff is 0 if evaluated during event month, or 1 if evaluated the month before.
        $diffInMonths = $this->offsetDate->copy()->startOfMonth()->diffInMonths($targetDate->copy()->startOfMonth(), false);

        return $diffInMonths >= 0 && $diffInMonths <= 1;
    }
}
