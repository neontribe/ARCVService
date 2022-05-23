<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use App\Specifications\IsUnderYears;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ChildIsBetweenOneAndPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'between 1 and start of primary school age';
    private $specification;

    /**
     * ChildIsBetweenOneAndPrimarySchoolAge constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn,
            new AndSpec(
                new NotSpec(new IsUnderYears(1, $this->offsetDate)),
                // Child school start date
                new IsUnderStartDate($this->offsetDate, 5, config('arc.school_month'))
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