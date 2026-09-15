<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsScottishAlmostStartDate;
use App\Specifications\IsScottishDeferralEligible;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ScottishChildCanDefer extends BaseChildEvaluation
{
    public $reason = 'able to defer (SCOTLAND)';
    private $specification;

    /**
     * ScottishChildCanDefer constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new AndSpec(
            new IsBorn(),
            new AndSpec(
                new IsScottishAlmostStartDate($this->offsetDate),
                new IsScottishDeferralEligible($this->offsetDate)
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
