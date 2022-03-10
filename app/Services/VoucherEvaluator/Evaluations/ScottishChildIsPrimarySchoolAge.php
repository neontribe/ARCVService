<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsUnderStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;
use Chalcedonyt\Specification\NotSpec;

class ScottishChildIsPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'is primary school age (SCOTLAND)';
    private $specification;

    /**
     * ScottishChildIsPrimarySchoolAge constructor.
     * @param Carbon|null $offsetDate
     * @param int|null $value
     */
    public function __construct(Carbon $offsetDate = null, int $value = null)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new IsBorn();
    }

    public function test($candidate)
    {
        parent::test($candidate);
        $monthNow = Carbon::now()->month;
        $schoolStartMonth = config('arc.scottish_school_month');
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        $year = $arr[0];
        if ($year == 'P') {
          return $this->fail();
        }
        $month = $arr[1];

        if ($year >= 5) {
          return $this->success();
        }
        // Otherwise, check if the start month has gone.
        if ($schoolStartMonth - $monthNow < 0) {
          $isAtSchool = false;
          // Are they still between 4 1 and 4 11 and not deferred OR are they over 5?
          if (((($year == 4 && $month >=1) || $year < 5) && !$candidate->deferred) || $year >= 5) {
            $isAtSchool = true;
          }
        } else {
          return $this->fail();
        }

        return ($this->specification->isSatisfiedBy($candidate) && $isAtSchool)
            ? $this->success()
            : $this->fail()
        ;
    }
}
