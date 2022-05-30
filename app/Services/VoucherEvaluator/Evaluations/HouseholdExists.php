<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class HouseholdExists extends BaseFamilyEvaluation
{
    public $reason = ' exists';
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
        // dd($candidate);
        return ($candidate->leaving_on === null)
            ? $this->success()
            : $this->fail()
        ;
    }
}
