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

        $this->specification = new AndSpec(
          // ( QUESTION ) - Is this worth having?
            new IsBorn()
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);
        // Is at least 4 years and 0 months NOW
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        \Log::info($arr[0]);
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $year = $arr[0];
        $month = $arr[1];
        $canStartSchool = false;
        if ($year == 4) {
          $canStartSchool = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && $canStartSchool)
            ? $this->success()
            : $this->fail()
        ;
    }
}
