<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use App\Specifications\IsUnderYears;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ScottishChildIsBetweenOneAndPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'is between 1 and start of primary school age (SCOTLAND)';
    private $specification;

    /**
     * ScottishChildIsBetweenOneAndPrimarySchoolAge constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn
            // new AndSpec(
            //     new NotSpec(new IsUnderYears(1, $this->offsetDate))
            // )
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);
        // $evaluator = EvaluatorFactory::make();
        // $evaluation = $evaluator->evaluate($this->family);
        // $rule = new ScottishChildIsPrimarySchoolAge();


        return ($this->specification->isSatisfiedBy($candidate))
            ? $this->success()
            : $this->fail()
        ;
    }
}
