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
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $year = $arr[0];
        $month = $arr[1];
        $isAtSchool = false;
        if ((($year == 4 && $month >=1) || $year <= 5) && !$candidate->deferred) {
          $isAtSchool = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && $isAtSchool)
            ? $this->success()
            : $this->fail()
        ;
    }
}
