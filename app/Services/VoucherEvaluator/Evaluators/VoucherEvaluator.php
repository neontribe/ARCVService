<?php

namespace App\Services\VoucherEvaluator\Evaluators;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\IEvaluee;

class VoucherEvaluator extends AbstractEvaluator
{
    private $evaluations = [];

    public function __construct($evaluations = [])
    {
        // Use the factory to make one of these
        $this->evaluations = $evaluations;
    }

    private function getNotices(IEvaluee $subject)
    {
        $notices = [];
        $rules = $this->evaluations[class_basename($subject)];
        foreach ($rules['notices'] as $rule) {
            $outcome = $rule->test($subject);
            if ($outcome) {
                $notices[] = ['reason' => class_basename($outcome::SUBJECT)."|".$outcome::REASON];
            }
        }
        return $notices;
    }

    private function getCredits(IEvaluee $subject)
    {
        $credits = [];
        $rules = $this->evaluations[class_basename($subject)];
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

    public function evaluateRegistration(Registration $subject)
    {
        /** @var Family $family */
        $family = $subject->family;
        return $family->accept($this);
    }
}