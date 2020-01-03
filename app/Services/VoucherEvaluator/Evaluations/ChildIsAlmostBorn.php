<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsAlmostYears;
use App\Specifications\IsBorn;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ChildIsAlmostBorn extends BaseChildEvaluation
{
    public $reason = 'almost born';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = 3)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new NotSpec(new IsBorn()),
            new IsAlmostYears(0, $this->offsetDate)
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);

        return ($this->specification->isSatisfiedBy($candidate))
            ? $this->success()
            : $this->fail()
        ;
    }
}