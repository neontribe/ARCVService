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

        $this->specification = new AndSpec(
            new IsBorn()
            // ( QUESTION - Is this bit worth having at all?)
            // This doesn't matter, because down there we check they are the right age to start school
            // Child school start date is coming up in a month (eg today is august-ish)
            // new IsAlmostStartDate($this->offsetDate, 5, config('arc.school_month'))
        );
    }

    public function test($candidate)
    {
        parent::test($candidate);
        // Is at least 4 years and 0 months NOW
        // Is not more than 4 years and 6 months NOW
        $format = '%y,%m';
        $age = $candidate->getAgeString($format);
        $arr = explode(",", $age, 2);
        $year = $arr[0];
        if ($arr[0] == 'P') {
          return $this->fail();
        }
        $month = $arr[1];
        $canDefer = false;
        if ($year == 4 && $month <= 6) {
          $canDefer = true;
        }

        return ($this->specification->isSatisfiedBy($candidate) && $canDefer)
            ? $this->success()
            : $this->fail()
        ;
    }
}
