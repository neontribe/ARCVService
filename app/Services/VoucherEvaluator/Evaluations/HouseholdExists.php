<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class HouseholdExists extends BaseFamilyEvaluation
{
    public $reason = 'exists';

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

        // Families that have left are disqualified by FamilyHasLeftProject.
        return $this->success();
    }
}
