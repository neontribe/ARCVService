<?php

namespace App\Services\VoucherEvaluator\Evaluators;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\Evaluations\AbstractEvaluation;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Services\VoucherEvaluator\Valuation;

class VoucherEvaluator extends AbstractEvaluator
{
    /** @var array $evaluations */
    public $evaluations = [];

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
     * Lazy way to query a specific rules case
     * @return bool
     */
    public function isVerifyingChildren()
    {
        $verifying = false;
        // Inelegant: asking the valuation by blunt path.
        if (isset($this->evaluations["App\Family"]["notices"]["FamilyHasUnverifiedChildren"])) {
            $evaluation = $this->evaluations["App\Family"]["notices"]["FamilyHasUnverifiedChildren"];
            if (!is_null($evaluation->value)) {
                $verifying = true;
            }
        }
        return $verifying;
    }

    /**
     * Lazy way to check a specific rules case
     * @return bool
     */
    public function isCreditingPrimaryKids()
    {
        $crediting = false;
        if (isset($this->evaluations["App\Child"]["credits"]["ChildIsPrimarySchoolAge"])) {
            $evaluation = $this->evaluations["App\Child"]["credits"]["ChildIsPrimarySchoolAge"];
            if (!is_null($evaluation->value)) {
                $crediting = true;
            }
        }
        return $crediting;
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
        if (array_key_exists($ruleKey, $rules)) {
            foreach ($rules[$ruleKey] as $rule) {
                // Dont process a rule if it's value is null (off)
                if (!is_null($rule->value)) {
                    $outcome = $rule->test($subject);
                    if ($outcome) {
                        $results[] = $outcome->toReason();
                    }
                }
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