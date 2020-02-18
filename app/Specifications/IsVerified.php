<?php

namespace App\Specifications;

use App\Child;
use Chalcedonyt\Specification\AbstractSpecification;

class IsVerified extends AbstractSpecification
{
    /**
     * Returns the child's born state
     *
     * @param Child $candidate
     * @return  Boolean
     */
    public function isSatisfiedBy(Child $candidate)
    {
        return $candidate->verified;
    }
}
