<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class FamilyIsPregnant extends BaseFamilyEvaluation
{
    public $reason = 'pregnant';

    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);
    }

    public function test($candidate)
    {
        parent::test($candidate);

        return ($candidate->expecting)
            ? $this->success()
            : $this->fail()
        ;
    }
}