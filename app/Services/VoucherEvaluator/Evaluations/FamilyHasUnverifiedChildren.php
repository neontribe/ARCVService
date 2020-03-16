<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsVerified;
use Carbon\Carbon;

class FamilyHasUnverifiedChildren extends BaseFamilyEvaluation
{
    public $reason = 'has one or more children that you haven\'t checked ID for yet';
    private $specification;

    /**
     * FamilyHasUnverifiedChildren constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);

        // Child is Verified specification
        $this->specification = new IsVerified();
    }

    /**
     * @param $candidate
     * @return FamilyHasUnverifiedChildren|void|null
     */
    public function test($candidate)
    {
        parent::test($candidate);

        $children = $candidate->children->all();

        // Get a list of kids who have ID.
        $satisfiers = array_filter(
            $children,
            function ($child) {
                // We satisfy if a Kid has ID
                return ($this->specification->isSatisfiedBy($child));
            }
        );

        // If the number of kids is not equal to kids with ID ...
        if (count($satisfiers) !== count($children)) {
            // ... there are some kids needing ID
            return $this->success();
        } else {
            // ... all kids have it, or there are no kids.
            return $this->fail();
        }
    }
}
