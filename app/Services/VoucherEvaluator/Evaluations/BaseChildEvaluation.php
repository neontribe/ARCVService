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

    public function toReason()
    {
        $reason = ['reason' => class_basename(self::SUBJECT)."|".$this->reason];
        // if there's an int value, include it
        return ($this->value > 0 || $this->value < 0)
            ? array_merge($reason, ['value' => $this->value ])
            : $reason
        ;
    }
}
