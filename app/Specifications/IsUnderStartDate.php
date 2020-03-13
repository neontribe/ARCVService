<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsUnderStartDate extends AbstractSpecification
{

    /** @var Carbon $offsetDate */
    protected $offsetDate;

    /** @var int $yearsAhead */
    private $yearsAhead;

    /** @var int $offsetMonth */
    private $offsetMonth;

    /**
     * IsUnderStartDate constructor.
     *
     * @param Carbon $offsetDate Usually today, unless a test had changed that.
     * @param int $yearsAhead How many years away we want to look
     * @param int $offsetMonth What month the event will be, usually configured to 9
     */
    public function __construct($offsetDate, int $yearsAhead, int $offsetMonth)
    {
        $this->offsetMonth = $offsetMonth;
        $this->yearsAhead = $yearsAhead;
        $this->offsetDate = $offsetDate;
    }

    /**
     * Tests an object and returns a boolean value
     * @param Child $candidate
     * @return  Boolean
     */
    public function isSatisfiedBy(Child $candidate)
    {
        // Generate the date of the event in question
        $targetDate = $candidate->calcFutureMonthYear($this->yearsAhead, $this->offsetMonth);

        // return (bool) if the offset date is less than that.
        return $this->offsetDate->lessThan($targetDate);
    }
}
