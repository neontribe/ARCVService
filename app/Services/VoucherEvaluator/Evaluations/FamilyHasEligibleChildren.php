<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Services\VoucherEvaluator\Valuation;
use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;
use Chalcedonyt\Specification\OrSpec;

class FamilyHasEligibleChildren extends BaseFamilyEvaluation
{
    const REASON = 'has no eligible children';
    private $specification;
    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);

        $this->specification = new OrSpec(
            new AndSpec(
                new IsBorn(),
                // Child school start date
                new IsUnderStartDate($this->offsetDate, 5, config('arc.school_month'))
            ),
            new NotSpec(new IsBorn())
        );
    }

    /**
     * @param $candidate
     * @return FamilyHasEligibleChildren|void|null
     */
    public function test($candidate)
    {
        parent::test($candidate);

        $children = $candidate->children;

        foreach ($children as $child) {
            if (
                $child->valuation->entitlement > 0 &&
                $this->specification->isSatisfiedBy($child)
            ) {
                return $this->success();
            }
        }
        // No eligible Children
        return $this->fail();
    }
}