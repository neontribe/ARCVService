<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;
use Chalcedonyt\Specification\OrSpec;

class FamilyHasNoEligibleChildren extends BaseFamilyEvaluation
{
    public $reason = 'has no eligible children';
    private $specification;
    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);

        // Either of
        $this->specification = new OrSpec(
            // Under school age
            new AndSpec(
                new IsBorn(),
                new IsUnderStartDate($this->offsetDate, 5, config('arc.school_month'))
            ),
            // OR a pregnancy
            new NotSpec(new IsBorn())
        );
    }

    /**
     * @param $candidate
     * @return FamilyHasNoEligibleChildren|void|null
     */
    public function test($candidate)
    {
        parent::test($candidate);

        $children = $candidate->children;

        // Test kids
        if (!empty(
            array_filter(
                $children,
                function ($child) {
                    // If we don't satisfy the rule
                    if (!$this->specification->isSatisfiedBy($child)) {
                        // This will trigger an evaluation if it hasn't have one already
                        $v = $child->valuation;
                        // And add a disqualification to the child.
                        // the next time we calculate if a kid is eligible, it will fail.
                        $v->disqualifications[] = new ChildPassesTestBecause('has no eligible siblings');
                    }
                }
            )
        )) {
            // There are no eligible Children
            $this->success();
        };
        // There were eligible Children
        return $this->fail();
    }
}