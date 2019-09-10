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
    private function getNotices(IEvaluee $subject)
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
    private function getCredits(IEvaluee $subject)
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
     * Evaluates a Child and returns the summary array
     *
     * @param Child $subject
     * @return array
     */
    public function evaluateChild(Child $subject)
    {
        $credits = $this->getCredits($subject);
        $notices = $this->getNotices($subject);

        $entitlement = array_sum(array_column($credits, 'value'));

        if (!$subject->born) {
            $eligibility = 'Pregnancy';
        } else {
            $eligibility = ($entitlement > 0)
                ? 'Eligible'
                : 'Ineligible'
            ;
        }

        return [
            'eligibility' => $eligibility,
            'notices' => $notices,
            'credits' => $credits,
            'entitlement' => $entitlement,
        ];
    }

    /**
     * Evaluates a Family object and returns the summary array
     *
     * @param Family $subject
     * @return array
     */
    public function evaluateFamily(Family $subject)
    {
        $credits = $this->getCredits($subject);
        $notices = $this->getNotices($subject);

        $children = $subject->children;
        /** @var Child $child */
        foreach ($children as $child) {
            $child_status = $child->accept($this);
            $notices = array_merge($notices, $child_status['notices']);
            $credits = array_merge($credits, $child_status['credits']);
        }

        $entitlement =  array_sum(array_column($credits, 'credits'));

        return [
            'credits' => $credits,
            'notices' => $notices,
            'entitlement' => $entitlement,
        ];
    }

    /**
     * Evaluates a registration and sets it's valuation
     *
     * @param Registration $subject
     */
    public function evaluateRegistration(Registration $subject)
    {
        /** @var Family $family */
        $family = $subject->family;
        $this->valuation = new Valuation($family->accept($this));
    }
}