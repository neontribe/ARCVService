<?php

namespace App\Services\VoucherEvaluator;

abstract class AbstractEvaluator
{
    abstract public function evaluate(IEvaluee $subject);
}
