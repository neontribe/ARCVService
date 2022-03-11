<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
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
                new IsBorn()
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
                // We satisfy them
                $isAtSchool = $this->isScottishChildAtSchool($child);
                $basicSpec = $this->specification->isSatisfiedBy($child);
                // \Log::info('dob' . $child->dob);
                // \Log::info('$isAtSchool' . $isAtSchool);
                // \Log::info('$basicSpec' . $basicSpec);
                if ($basicSpec && $isAtSchool) {
                  return false;
                } else {
                  return true;
                }
            }
        );

        // Check if there are kids who might qualify others ...
        if (!$satisfiers) {
            // ... there are none, pass this rule
            return $this->success();
        } else {
            // ... there are some, fail this rule.
            return $this->fail();
        }
    }

    public function isScottishChildAtSchool($candidate)
    {
      $monthNow = Carbon::now()->month;
      $schoolStartMonth = config('arc.scottish_school_month');
      $format = '%y,%m';
      $age = $candidate->getAgeString($format);
      $arr = explode(",", $age, 2);
      $year = $arr[0];
      if ($year == 'P') {
        return false;
      }
      $month = $arr[1];

      if ($year >= 5) {
        return true;
      }
      if ($year < 4) {
        return false;
      }
      // Otherwise, check if the start month has gone.
      if ($schoolStartMonth - $monthNow < 0) {
        $isAtSchool = false;
        // Are they still between 4 1 and 4 11 and not deferred OR are they over 5?
        if (((($year == 4 && $month >=1) || $year < 5) && !$candidate->deferred) || $year >= 5) {
          $isAtSchool = true;
        }
      } else {
        return false;
      }

      return $isAtSchool;
    }
}
