<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Chalcedonyt\Specification\AndSpec;

class ScottishChildIsAlmostPrimarySchoolAge extends BaseChildEvaluation
{
    public $reason = 'is almost primary school age';
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
            new IsBorn(),
            // Child school start date is coming up in a month (eg today is august-ish)
            new IsAlmostStartDate($this->offsetDate, 5, 8)
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);
        $d = date_parse_from_format("Y-m-d", $candidate->dob);
        $month = $d['month'];
        $tooYoung = false;
        if ($month > 3 && $month < 9) {
          $tooYoung = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && !$tooYoung)
            ? $this->success()
            : $this->fail()
        ;
    }
}
