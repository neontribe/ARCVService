<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

/**
 * Disqualifies families that are not on the project (Family::status() is false).
 *
 * This is the only place leavers are handled: HouseholdExists and HouseholdMember
 * credit any family. If a sponsor overrides this rule to NULL, leavers will get
 * household credit again.
 */
class FamilyHasLeftProject extends BaseFamilyEvaluation
{
    public $reason = 'has left the project';

    /**
     * FamilyHasLeftProject constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);
    }

    public function test($candidate)
    {
        parent::test($candidate);

        // Disqualify families that are not currently on the project
        return $candidate->status()
            ? $this->fail()
            : $this->success()
        ;
    }
}
