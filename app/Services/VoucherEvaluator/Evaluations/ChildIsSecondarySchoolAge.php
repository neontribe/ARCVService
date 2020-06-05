<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ChildIsSecondarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'is secondary school age';
    private $specification;

    /**
     * ChildIsSecondarySchoolAge constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new notSpec(new IsUnderStartDate($this->offsetDate, 12, config('arc.school_month')))
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