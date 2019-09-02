<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsUnderStartDate extends AbstractSpecification
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
        $targetDate = $candidate->calcSchoolStart();
        return $this->offsetDate->lessThan($targetDate);
    }

}
