<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class HouseholdMember extends BaseChildEvaluation
{
    public $reason = 'member of the household';
    private $specification;

    /**
     * HouseholdMember constructor.
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);
    }

    public function test($candidate)
    {
        parent::test($candidate);

        // A household member only earns credit while their Family is still active.
        // leaving_on / rejoin_on live on the Family, not the Child.
        $family = $candidate->family;

        return ($family !== null && $family->status())
            ? $this->success()
            : $this->fail()
        ;
    }
}
