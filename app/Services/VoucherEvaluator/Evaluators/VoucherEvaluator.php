<?php

namespace App\Services\VoucherEvaluator\Evaluators;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\FamilyIsPregnant;
use App\Services\VoucherEvaluator\IEvaluee;
use Carbon\Carbon;

class VoucherEvaluator extends AbstractEvaluator
{
    private $evaluations = [];

    public function __construct($evaluations = null, Carbon $offsetDate = null)
    {
        $this->offSetDate = $offsetDate ?? Carbon::today()->startOfDay();

        $defaultEvals = [
            Child::class => [
                'notices' => [
                    new ChildIsUnBorn($offsetDate),
                    new ChildIsAlmostBorn($offsetDate),
                    new ChildIsAlmostOne($offsetDate),
                    new ChildIsAlmostSchoolAge($offsetDate)
                ],
                'credits' => [
                    new ChildIsUnderOne($offsetDate, 3),
                    new ChildIsUnderSchoolAge($offsetDate, 3)
                ]
            ],
            Family::class => [
                [
                    'notices' => [],
                    'credits' => [
                        new FamilyIsPregnant(null, 3)
                    ]
                ]
            ],
            Registration::class => []
        ];

        $this->evaluations = $evaluations ?? $defaultEvals;
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
                    'value' => $outcome->value
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
            'entitlement' => $entitlement
        ];
    }

    public function evaluateFamily(Family $subject)
    {
        $credits = $this->getCredits($subject);
        $notices = $this->getNotices($subject);

        $entitlement =  array_sum(array_column($credits, 'credits'));

        return [
            'credits' => $credits,
            'notices' => $notices,
            'entitlement' => $entitlement
        ];
    }

    public function evaluateRegistration(Registration $subject)
    {
        // TODO: Implement evaluateRegistration() method.
    }
}