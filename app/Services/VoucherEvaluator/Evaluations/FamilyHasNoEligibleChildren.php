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
    public $reason = 'no eligible children';
    private $specification;
    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);

        // Pregnancies or under school age.
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

        $children = $candidate->children->all();

        $satisfiers = array_filter(
            $children,
            function ($child) {
                // If we satisfy the rule
                return ($this->specification->isSatisfiedBy($child));
            }
        );

        if (empty($satisfiers)) {
            // There are no qualifying kids
            return $this->success();
        } else {
            return $this->fail();
        }
    }
}