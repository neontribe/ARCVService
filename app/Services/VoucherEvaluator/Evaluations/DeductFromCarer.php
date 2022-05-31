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
        $numOfChildren = $candidate->children->count();
        // This rule should only apply to the first 'child', who will be the carer.
        return ($numOfChildren > 1)
            ? $this->success()
            : $this->fail()
        ;
    }
}
