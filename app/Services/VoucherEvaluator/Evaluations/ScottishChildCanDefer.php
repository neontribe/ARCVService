<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ScottishChildCanDefer extends BaseChildEvaluation
{
    public $reason = 'is able to defer (SCOTLAND)';
    private $specification;

    /**
     * ScottishChildCanDefer constructor.
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
        // Check we're in the start month or the one before.
        if (($schoolStartMonth - $monthNow > 1) || ($schoolStartMonth - $monthNow < 0)) {
          return $this->fail();
        }
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        $year = $arr[0];
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $month = $arr[1];
        $canDefer = false;
        if ($year === '4' && ($month >= 1 && $month <= 6)) {
          $canDefer = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && $canDefer)
            ? $this->success()
            : $this->fail()
        ;
    }
}
