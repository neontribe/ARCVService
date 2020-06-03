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
    public $reason = 'in need of under ones to qualify primary schoolers';
    private $specification;
    /**
     * FamilyIsPregnant constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

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

        // Get a list of kids who can qualify others
        $satisfiers = array_filter(
            $children,
            function ($child) {
                // We satisfy them
                return ($this->specification->isSatisfiedBy($child));
            }
        );

        // Check if there are kids who might qualify others ...
        if (empty($satisfiers)) {
            // ... there are none, pass this rule
            return $this->success();
        } else {
            // ... there are some, fail this rule.
            return $this->fail();
        }
    }
}