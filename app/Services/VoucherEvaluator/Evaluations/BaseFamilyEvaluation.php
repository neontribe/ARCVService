<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Family;
use InvalidArgumentException;

class BaseFamilyEvaluation extends AbstractEvaluation
{
    const SUBJECT = Family::class;

    public function test($candidate)
    {
        $subject = self::SUBJECT;

        if (!isset($candidate) || !$candidate instanceof $subject) {
            throw new InvalidArgumentException("Argument 1 must be instance of " . $subject);
        }
    }
}