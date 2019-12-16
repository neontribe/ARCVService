<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class FamilyHasNoEligibleChildren extends BaseFamilyEvaluation
{
    const REASON = 'No eligible children';

    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     */
    public function __construct(Carbon $offsetDate = null)
    {
        parent::__construct($offsetDate);
    }

    /**
     * @param $candidate
     * @return FamilyHasNoEligibleChildren|void|null
     */
    public function test($candidate)
    {
        parent::test($candidate);

        $children = $candidate->children;

        foreach ($children as $child) {
            if ($child->valuation->hasSatisfiedEvaluation(ChildIsUnderSchoolAge::class)) {
                // We have eligible children, so we fail
                return $this->fail();
            }
        }
        // No eligible Children! success!
        return $this->success();
    }
}