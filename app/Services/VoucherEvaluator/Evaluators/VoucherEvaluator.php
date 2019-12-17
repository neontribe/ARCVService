<?php

namespace App\Services\VoucherEvaluator\Evaluators;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\Evaluations\AbstractEvaluation;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Services\VoucherEvaluator\Valuation;

class VoucherEvaluator extends AbstractEvaluator
{
    /** @var array $evaluations */
    private $evaluations = [];

    /**
     * VoucherEvaluator constructor.
     *
     * @param array $evaluations
     */
    public function __construct(array $evaluations)
    {
        // Use the factory to make one of these
        $this->evaluations = $evaluations;
    }

    /**
     * @param IEvaluee $subject
     * @return Valuation
     */
    public function evaluate(IEvaluee $subject)
    {
        return new Valuation([
            'evaluee' => $subject,
            'valuations' => $this->evaluateRelations($subject),
            'credits' => $this->evaluateRules('credits', $subject),
            'notices' => $this->evaluateRules('notices', $subject),
            'disqualifiers' => $this->evaluateRules('disqualifiers', $subject),
        ]);
    }

    /**
     * Evaluates a specific rule key against the Evaluee
     *
     * @param string $ruleKey
     * @param IEvaluee $subject
     * @return array
     */
    private function evaluateRules(string $ruleKey, IEvaluee $subject)
    {
        $results = [];
        $rules = $this->evaluations[get_class($subject)];
        /**
         * @var AbstractEvaluation $rule
         * @var AbstractEvaluation $outcome
         */
        foreach ($rules[$ruleKey] as $rule) {
            $outcome = $rule->test($subject);
            if ($outcome) {
                $results[] = $outcome->toReason();
            }
        }
        return $results;
    }

    /**
     * Calls relation models to evaluate them
     * returns an array of valuations, one for each model.
     *
     * @param IEvaluee $subject
     * @return array
     */
    private function evaluateRelations(IEvaluee $subject)
    {
        /*
         * Currently we only plan on feeding models to this - If we start
         * applying it to standard Models we'll need to expand it to deal.
        */
        $valuations = [];
        $rules = $this->evaluations[get_class($subject)];

        foreach ($rules['relations'] as $relationName) {
            // Executes the given relationship
            $relation = $subject->getRelationValue($relationName);
            // could be a single Model, array it.
            $relationIterable = (is_iterable($relation)) ? $relation : [$relation];

            /** @var IEvaluee $relationModel */
            foreach ($relationIterable as $relationModel) {
                $valuations[] = $relationModel->accept($this);
            }
        }
        return $valuations;
    }
}