<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsAlmostStartDate extends AbstractSpecification
{
    /** @var Carbon $offsetDate */
    protected $offsetDate;

    /**
    *  @param Carbon  $offsetDate   Whenever you want "today" to be
    */
    public function __construct(Carbon $offsetDate)
    {
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
        $targetDate = $candidate->calcSchoolStart();
        return $targetDate->isFuture() &&
            $this->offsetDate->diffInMonths($targetDate) <=1;
    }
}
