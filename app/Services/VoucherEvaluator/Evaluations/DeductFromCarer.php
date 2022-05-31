<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class DeductFromCarer extends BaseFamilyEvaluation
{
    public $reason = '';
    private $specification;

    /**
     * DeductFromCarer constructor.
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);
    }

    public function test($candidate)
    {
        parent::test($candidate);
        return ($candidate->has('children'))
            ? $this->success()
            : $this->fail()
        ;
    }
}
