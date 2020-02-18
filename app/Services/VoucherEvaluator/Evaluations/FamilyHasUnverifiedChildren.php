<?php

namespace App\Services\VoucherEvaluator\Evaluations;


use App\Specifications\IsVerified;
use Carbon\Carbon;

class FamilyHasUnverifiedChildren extends BaseFamilyEvaluation
{
    public $reason = 'has children needing ID';
    private $specification;
    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);

        // Pregnancies or under school age.
        $this->specification = new IsVerified();
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