<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Services\VoucherEvaluator\IEvaluation;
use Carbon\Carbon;

abstract class AbstractEvaluation implements IEvaluation
{
    /** @var integer $value */
    public $value;

    /** @var string $reason */
    public $reason;

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
        return $this;
    }

    protected function fail()
    {
        return null;
    }

    public function toReason()
    {
        $reason = ['reason' => $this->reason];
        // if there's a value, include it
        return ($this->value)
            ? array_merge($reason, ['value' => $this->value ])
            : $reason
        ;
    }
}