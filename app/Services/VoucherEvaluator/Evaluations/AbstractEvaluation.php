<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Services\VoucherEvaluator\IEvaluation;
use Carbon\Carbon;

abstract class AbstractEvaluation implements IEvaluation
{
    /** @var integer $value */
    public $value;

    /** @var Carbon $offsetDate */
    protected $offsetDate;

    public function __construct(Carbon $offsetDate = null, $value = null)
    {
        $this->value = $value;
        $this->offsetDate = (isset($offsetDate) && $offsetDate != null)
            ? $offsetDate
            : Carbon::today()->startOfDay();
    }

    abstract public function test($candidate);

    protected function success()
    {
        //die(print_r(get_class($this)));
        return $this;
    }

    protected function fail()
    {
        return null;
    }
}