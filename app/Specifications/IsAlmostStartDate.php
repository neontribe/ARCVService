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
     * @param $offsetDate
     * @param int $yearsAhead
     * @param int $offsetMonth
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
        /** @var Carbon $targetDate */
        $targetDate = $candidate->calcFutureMonthYear($this->yearsAhead, $this->offsetMonth);
        return $targetDate->isFuture() &&
            // If it's *this* month or *next* month, not *last* month
            $this->offsetDate->diffInMonths($targetDate) <=1;
    }
}
