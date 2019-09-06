<?php

namespace App\Services\VoucherEvaluator;

use App\Child;
use App\Family;
use App\Registration;

abstract class AbstractEvaluator
{
    abstract public function evaluateChild(Child $subject);
    abstract public function evaluateFamily(Family $subject);
    abstract public function evaluateRegistration(Registration $subject);
}