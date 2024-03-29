<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ChildIsAlmostPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'almost primary school age';
    private $specification;

    /**
     * ChildIsAlmostPrimarySchoolAge constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            // Child school start date is coming up in a month (eg today is august-ish)
            new IsAlmostStartDate($this->offsetDate, 5, config('arc.school_month'))
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