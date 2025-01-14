<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsAlmostYears extends AbstractSpecification
{
    protected int $years;

    protected Carbon $offsetDate;

    /**
    *  @param integer $years        How many years old they'll be
    *  @param Carbon  $offsetDate   Whenever you want "today" to be
    */
    public function __construct(int $years, Carbon $offsetDate)
    {
        $this->years = $years;
        $this->offsetDate = $offsetDate;
    }

    /**
    * Tests an object and returns a boolean value
    * @param Child $candidate
    * @return  Boolean
    */
    public function isSatisfiedBy(Child $candidate): bool
    {
        /** @var Carbon $targetDate */
        $targetDate = $candidate->dob->endOfMonth()->addYears($this->years);
        return $targetDate->isFuture() &&
            (int) $this->offsetDate->diffInMonths($targetDate) <= 1;
    }

}
