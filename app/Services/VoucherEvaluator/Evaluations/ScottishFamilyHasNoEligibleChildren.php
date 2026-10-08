<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsScottishUnderSchoolAge;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;
use Chalcedonyt\Specification\OrSpec;

class ScottishFamilyHasNoEligibleChildren extends BaseFamilyEvaluation
{
    public $reason = 'has no child under primary school age then children of primary school age get (SCOTLAND)';
    private $specification;

    /**
     * ScottishFamilyHasNoEligibleChildren constructor.
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
                new IsScottishUnderSchoolAge($this->offsetDate)
            ),
            // OR a pregnancy
            new NotSpec(new IsBorn())
        );
    }

    /**
     * @param $candidate
     * @return ScottishFamilyHasNoEligibleChildren|void|null
     */
    public function test($candidate)
    {
        parent::test($candidate);

        $children = $candidate->children->all();

        // Get a list of kids who can qualify others
        $satisfiers = array_filter(
            $children,
            function ($child) {
                return $this->specification->isSatisfiedBy($child);
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
