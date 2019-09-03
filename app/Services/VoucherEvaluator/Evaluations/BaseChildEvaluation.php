<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Child;
use App\Services\VoucherEvaluator\IEvaluation;
use Carbon\Carbon;
use InvalidArgumentException;

class BaseChildEvaluation implements IEvaluation
{
    const SUBJECT = Child::class;

    /** @var integer $value */
    protected $value;

    /** @var Carbon $offsetDate */
    protected $offsetDate;

    public function __construct(Carbon $offsetDate = null, $value = null)
    {
        //die(print_r(self::SUBJECT));
        $this->value = $value;
        $this->offsetDate = (isset($offsetDate) && $offsetDate != null)
            ? $offsetDate
            : Carbon::today()->startOfDay();
    }

    public function test($candidate)
    {
        $subject = self::SUBJECT;



        if (!isset($candidate) || !$candidate instanceof $subject) {
            throw new InvalidArgumentException("Argument 1 must be instance of " . $subject);
        }
    }

    protected function success()
    {
        return $this;
    }

    protected function fail()
    {
        return null;
    }
}