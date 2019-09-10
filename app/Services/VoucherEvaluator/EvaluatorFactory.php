<?php

namespace App\Services\VoucherEvaluator;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostTwelve;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnBorn;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderSchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderTwelve;
use App\Services\VoucherEvaluator\Evaluations\FamilyIsPregnant;
use App\Services\VoucherEvaluator\Evaluators\VoucherEvaluator;
use Carbon\Carbon;

class EvaluatorFactory
{
    const EXTENDED_SPONSORS = ['SK'];

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

    public static function makeFromRegistration(Registration $registration, $offsetDate = null)
    {
        return (in_array($registration->centre->sponsor->shortcode, self::EXTENDED_SPONSORS))
            ? self::make('extended_age', $offsetDate)
            : self::make(null, $offsetDate)
        ;
    }

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

    private static function createExtendedEvaluations(Carbon $offsetDate)
    {
        return $evaluations = [
            Child::class => [
                'notices' => [
                    new ChildIsUnBorn($offsetDate),
                    new ChildIsAlmostBorn($offsetDate),
                    new ChildIsAlmostOne($offsetDate),
                    new ChildIsAlmostSchoolAge($offsetDate),
                    new ChildIsAlmostTwelve($offsetDate)
                ],
                'credits' => [
                    new ChildIsUnderOne($offsetDate, 3),
                    new ChildIsUnderSchoolAge($offsetDate, 3),
                    new ChildIsUnderTwelve($offsetDate, 3)
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