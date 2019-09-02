<?php

namespace App\Services\VoucherEvaluator;

interface IEvaluation
{
    public function test($candidate);
}