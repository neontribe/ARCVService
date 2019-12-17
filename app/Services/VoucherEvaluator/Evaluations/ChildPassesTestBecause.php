<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use Carbon\Carbon;

class ChildPassesTestBecause extends BaseChildEvaluation
{
    public $reason;

    public function __construct($reason = null)
    {
        parent::__construct();
        $this->reason = $reason;
    }

    public function test($candidate)
    {
        parent::test($candidate);

        // Always return true
        return $this->success();
    }
}