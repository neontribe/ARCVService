<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderYears;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ChildIsOverDue extends BaseChildEvaluation
{
    const REASON = 'over due date';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = 3)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new NotSpec(new IsBorn()),
            new NotSpec(new IsUnderYears(0, $this->offsetDate))
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