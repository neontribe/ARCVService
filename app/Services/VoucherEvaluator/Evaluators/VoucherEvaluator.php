<?php

namespace App\Services\VoucherEvaluator\Evaluators;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Services\VoucherEvaluator\Valuation;

class VoucherEvaluator extends AbstractEvaluator
{
    /** @var array $evaluations */
    private $evaluations = [];

    /** @var Valuation $valuation */
    public $valuation;

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
     * Helper to process the current valuation Notices
     *
     * @param IEvaluee $subject
     * @return array
     */
    private function evaluateNotices(IEvaluee $subject)
    {
        $notices = [];
        $rules = $this->evaluations[get_class($subject)];
        foreach ($rules['notices'] as $rule) {
            $outcome = $rule->test($subject);
            if ($outcome) {
                $notices[] = ['reason' => class_basename($outcome::SUBJECT)."|".$outcome::REASON];
            }
        }
        return $notices;
    }

    /**
     * Helper to process the current valuation credits
     *
     * @param IEvaluee $subject
     * @return array
     */
    private function evaluateCredits(IEvaluee $subject)
    {
        $credits = [];
        $rules = $this->evaluations[get_class($subject)];
        foreach ($rules['credits'] as $rule) {
            $outcome = $rule->test($subject);
            if ($outcome !== null) {
                $credits[] = [
                    'reason' => class_basename($outcome::SUBJECT)."|".$outcome::REASON,
                    'value' => $outcome->value,
                ];
            }
        }
        return $credits;
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
            $relation = (is_iterable($relation)) ?: [$relation];

            /** @var IEvaluee $relationModel */
            foreach ($relation as $relationModel) {
                $valuations[] = $relationModel->accept($this);
            }
        }
        return $valuations;
    }

    /**
     * Evaluates a Child and returns the valuation
     *
     * @param Child $subject
     * @return Valuation
     */
    public function evaluateChild(Child $subject)
    {
        $valuations = $this->evaluateRelations($subject);
        $credits = $this->evaluateCredits($subject);
        $notices = $this->evaluateNotices($subject);

        $entitlement = array_sum(array_column($credits, 'value'));

        $eligibility = ($entitlement > 0)
            ? 'Eligible'
            : 'Ineligible'
        ;

        return new Valuation([
            'valuations' => $valuations,
            'evaluee' => $subject,
            'eligibility' => $eligibility,
            'notices' => $notices,
            'credits' => $credits,
            'entitlement' => $entitlement,
        ]);
    }

    /**
     * Evaluates a Family object and returns the summary array
     *
     * @param Family $subject
     * @return Valuation
     */
    public function evaluateFamily(Family $subject)
    {
        $valuations = $this->evaluateRelations($subject);
        $credits = $this->evaluateCredits($subject);
        $notices = $this->evaluateNotices($subject);
        $entitlement =  array_sum(array_column($credits, 'value'));

        return new Valuation([
            'valuations' => $valuations,
            'evaluee' => $subject,
            'credits' => $credits,
            'notices' => $notices,
            'entitlement' => $entitlement,
        ]);
    }

    /**
     * Evaluates a registration and sets it's valuation
     *
     * @param Registration $subject
     */
    public function evaluateRegistration(Registration $subject)
    {
        $credits = $this->evaluateCredits($subject);
        $notices = $this->evaluateNotices($subject);

        $entitlement =  array_sum(array_column($credits, 'value'));

        $valuations = $this->evaluateRelations($subject);

        $this->valuation = new Valuation([
            'valuations' => $valuations,
            'evaluee' => $subject,
            'credits' => $credits,
            'notices' => $notices,
            'entitlement' => $entitlement,
        ]);
    }
}