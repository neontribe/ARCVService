<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsAlmostStartDate extends AbstractSpecification
{

    /** @var Carbon $offsetDate */
    protected $offsetDate;

    /** @var int $yearsAhead */
    private $yearsAhead;

    /** @var int $offsetMonth */
    private $offsetMonth;

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
    public function isSatisfiedBy(Child $candidate)
    {
        /** @var Carbon $targetDate */
        // Generate the date of the event in question
        $targetDate = $candidate->calcFutureMonthYear($this->yearsAhead, $this->offsetMonth);

        // Return (bool) if it's not happened yet AND If that date will happen this OR next month
        return $targetDate->isFuture() &&
            // If it's *this* month or *next* month, not *last* month
            $this->offsetDate->diffInMonths($targetDate) <=1;
    }
}
