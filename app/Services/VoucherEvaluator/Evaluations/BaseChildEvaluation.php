<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Child;
use InvalidArgumentException;

class BaseChildEvaluation extends AbstractEvaluation
{
    const SUBJECT = Child::class;

    public function test($candidate)
    {
        $subject = self::SUBJECT;

        if (!isset($candidate) || !$candidate instanceof $subject) {
            throw new InvalidArgumentException("Argument 1 must be instance of " . $subject);
        }
    }
}
