<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ScottishChildIsAlmostPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'is almost primary school age (SCOTLAND)';
    private $specification;

    /**
     * ScottishChildIsAlmostPrimarySchoolAge constructor.
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
        if (config('app.env') == 'local' || config('app.env') == 'staging') {
          $schoolStartMonth = $monthNow + 1;
        } else {
          $schoolStartMonth = config('arc.scottish_school_month');
        }
        // Check we're in the start month or the one before.
        if ($schoolStartMonth - $monthNow > 1) {
          $this->fail();
        }

        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $year = $arr[0];
        $month = $arr[1];
        $canStartSchool = false;
        if (($year == 4 && $month >=1) || ($year == 5 && $month = 0)) {
          $canStartSchool = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && $canStartSchool)
            ? $this->success()
            : $this->fail()
        ;
    }
}
