<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ChildIsUnderSchoolAge extends BaseChildEvaluation
{
    const REASON = 'under school age';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = 3)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new IsUnderStartDate($this->offsetDate)
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