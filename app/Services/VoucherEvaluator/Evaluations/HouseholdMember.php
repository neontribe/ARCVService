<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class HouseholdMember extends BaseChildEvaluation
{
    public $reason = 'member of the household';

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

        // Families that have left are disqualified by FamilyHasLeftProject.
        return $candidate->family !== null
            ? $this->success()
            : $this->fail()
        ;
    }
}
