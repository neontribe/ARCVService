<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ChildIsPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'primary school age';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = 3)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new AndSpec(
                // Not under primary school age ...
                new notSpec(
                    new IsUnderStartDate($this->offsetDate, 5, config('arc.school_month'))
                ),
                // but _is_ under secondary school age
                new IsUnderStartDate($this->offsetDate, 12, config('arc.school_month'))
            )
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