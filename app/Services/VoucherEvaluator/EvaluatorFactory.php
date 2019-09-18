<?php

namespace App\Services\VoucherEvaluator;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostExtendedAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderExtendedAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\FamilyIsPregnant;
use App\Services\VoucherEvaluator\Evaluators\VoucherEvaluator;
use Carbon\Carbon;

class EvaluatorFactory
{
    /**
     * Factory method that makes the evaluator with the correct rules
     *
     * @param null $type
     * @param Carbon|null $offsetDate
     * @return VoucherEvaluator
     */
    public static function make($type = null, Carbon $offsetDate = null)
    {
        $offsetDate = $offsetDate ?? Carbon::today()->startOfDay();

        switch ($type) {
            case "extended_age":
                $evaluations = self::createExtendedEvaluations($offsetDate);
                break;
            default:
                $evaluations = self::createStandardEvaluations($offsetDate);
        }
        return new VoucherEvaluator($evaluations);
    }

    /**
     * Factory method that uses the Registration for the correct context
     *
     * @param Registration $registration
     * @param null $offsetDate
     * @return VoucherEvaluator
     */
    public static function makeFromRegistration(Registration $registration, $offsetDate = null)
    {
        // Get the list of extended sponsors from config
        // TODO: when we have more variations of rulessets, make this better
        $extended_sponsors = config('arc.extended_sponsors');

        return (in_array($registration->centre->sponsor->shortcode, $extended_sponsors))
            ? self::make('extended_age', $offsetDate)
            : self::make(null, $offsetDate)
        ;
    }

    /**
     * Creates the correct rules for a "standard" Evaluation
     *
     * @param Carbon $offsetDate
     * @return array
     */
    private static function createStandardEvaluations(Carbon $offsetDate)
    {
        return $evaluations = [
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
                'notices' => [],
                'credits' => [
                    new FamilyIsPregnant(null, 3)
                ]
            ],
        ];
    }

    /**
     * Creates the correct rule for an "extended age" evaluation
     *
     * @param Carbon $offsetDate
     * @return array
     */
    private static function createExtendedEvaluations(Carbon $offsetDate)
    {
        return $evaluations = [
            Child::class => [
                'notices' => [
                    new ChildIsUnBorn($offsetDate),
                    new ChildIsAlmostBorn($offsetDate),
                    new ChildIsAlmostOne($offsetDate),
                    new ChildIsAlmostExtendedAge($offsetDate)
                ],
                'credits' => [
                    new ChildIsUnderOne($offsetDate, 3),
                    new ChildIsUnderExtendedAge($offsetDate, 3)
                ]
            ],
            Family::class => [
                'notices' => [],
                'credits' => [
                    new FamilyIsPregnant(null, 3)
                ]
            ],
        ];
    }
}