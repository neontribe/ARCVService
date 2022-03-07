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
        // Is at least 4 years and 0 months NOW
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $year = $arr[0];
        $month = $arr[1];
        $canStartSchool = false;
        if (config('app.env') == 'local' || config('app.env') == 'staging') {
          if (($year == 4 && $month >=5) || ($year == 5 && $month <= 4)) {
            $canStartSchool = true;
          }
        } else {
          if (($year == 4 && $month >=1) || ($year == 5 && $month = 0)) {
            $canStartSchool = true;
          }
        }

        // 1 March 2017 - 28 February 2018 - for AUG
        // 5 0 - 4 1 = AGE NOW
        // 1 Nov 2016 - 28 Oct 2017 - for APR (testing purposes)
        // 5 4 - 4 5 = AGE NOW


        return ($this->specification->isSatisfiedBy($candidate) && $canStartSchool)
            ? $this->success()
            : $this->fail()
        ;
    }
}