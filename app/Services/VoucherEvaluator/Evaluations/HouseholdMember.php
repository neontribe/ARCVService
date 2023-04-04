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

        return ($candidate->leaving_on === null || $candidate->rejoin_on > $candidate->leaving_on)
            ? $this->success()
            : $this->fail()
        ;
    }
}
