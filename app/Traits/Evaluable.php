<?php

namespace App\Traits;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\Valuation;

trait Evaluable
{
    /**
     * Private evaluator stash
     * @var AbstractEvaluator null
     */
    private $_evaluator = null;

    /**
     * Valuation stash
     * @var Valuation $_valuation
     */
    private $_valuation = null;

    /**
     * Magically gets a public evaluator.
     * @return AbstractEvaluator
     */
    public function getEvaluator()
    {
        // if the private var is null, make a new one, stash it and return it.
        $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::make();
        return $this->_evaluator;
    }

    /**
     * Get the valuation on this registration.
     *
     * @return Valuation
     */
    public function getValuation()
    {
        // Get the stashed one, or get a new one.
        return ($this->_valuation) ?? $this->accept($this->getEvaluator());
    }

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     * @return Valuation
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        $this->_valuation = $evaluator->evaluate($this);
        return $this->_valuation;
    }
}
