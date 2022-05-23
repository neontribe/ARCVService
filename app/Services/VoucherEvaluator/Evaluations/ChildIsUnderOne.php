<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderYears;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ChildIsUnderOne extends BaseChildEvaluation
{
    public $reason = 'under 1 year old';
    private $specification;

    /**
     * ChildIsUnderOne constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new IsUnderYears(1, $this->offsetDate)
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