<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class FamilyIsPregnant extends BaseFamilyEvaluation
{
    public $reason = 'pregnant';

    public function __construct(Carbon $offsetDate = null, $value = 3)
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