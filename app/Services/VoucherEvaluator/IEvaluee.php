<?php

namespace App\Services\VoucherEvaluator;

interface IEvaluee
{
    // Visitor pattern
    public function accept(AbstractEvaluator $visitorIn);
}