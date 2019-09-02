<?php

namespace App\Specifications;

use App\Child;
use Chalcedonyt\Specification\AbstractSpecification;

class IsBorn extends AbstractSpecification
{
    /**
    * Tests an object and returns a boolean value
    * @param Child $candidate
    * @return  Boolean
    */
    public function isSatisfiedBy(Child $candidate)
    {
        return $candidate->born;
    }
}
