<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostYears;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ChildIsAlmostTwelve extends BaseChildEvaluation
{
    const REASON = 'is almost twelve';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new IsAlmostYears(12, $this->offsetDate)
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