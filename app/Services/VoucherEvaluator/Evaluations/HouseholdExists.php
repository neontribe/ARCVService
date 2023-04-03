<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class HouseholdExists extends BaseFamilyEvaluation
{
    public $reason = 'exists';
    private $specification;

    /**
     * HouseholdExists constructor.
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
