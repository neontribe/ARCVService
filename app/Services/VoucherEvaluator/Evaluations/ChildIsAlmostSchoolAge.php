<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ChildIsAlmostSchoolAge extends BaseChildEvaluation
{
    const REASON = 'is almost school age';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new IsAlmostStartDate($this->offsetDate)
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);

        ($this->specification->isSatisfiedBy($candidate))
            ? $this->success()
            : $this->fail()
        ;
    }
}