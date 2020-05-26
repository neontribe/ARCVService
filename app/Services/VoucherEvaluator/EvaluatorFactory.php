<?php

namespace App\Services\VoucherEvaluator;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostSecondarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsBetweenOneAndPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsSecondarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderOne;
use App\Services\VoucherEvaluator\Evaluations\FamilyHasNoEligibleChildren;
use App\Services\VoucherEvaluator\Evaluations\FamilyHasUnverifiedChildren;
use App\Services\VoucherEvaluator\Evaluations\FamilyIsPregnant;
use App\Services\VoucherEvaluator\Evaluators\VoucherEvaluator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EvaluatorFactory
{
    /**
     * Factory method that makes the evaluator with the correct rules
     *
     * @param Collection|null $modEvaluations
     * @param Carbon|null $offsetDate
     * @return VoucherEvaluator
     */
    public static function make(Collection $modEvaluations = null, Carbon $offsetDate = null)
    {
        $modEvaluations = $modEvaluations ?? collect([]);
        $offsetDate = $offsetDate ?? Carbon::today()->startOfDay();
        $evaluations = self::generateEvaluations($modEvaluations, $offsetDate);
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
        // Look up Sponsor rules specific to our Registration
        $evaluations = $registration->centre->sponsor->evaluations;
        return self::make($evaluations, $offsetDate);
    }

    /**
     * Combines the standard evaluations with specific modifications.
     *
     * @param Collection $modEvaluations
     * @param Carbon $offsetDate
     * @return array
     */
    public function generateEvaluations(Collection $modEvaluations, Carbon $offsetDate)
    {
        $evaluations = [
            "App\Child" => [
                'credits' => [
                    "ChildIsUnderOne" => new ChildIsUnderOne($offsetDate, 6),
                    "ChildIsBetweenOneAndPrimarySchoolAge" => new ChildIsBetweenOneAndPrimarySchoolAge($offsetDate, 3),
                    "ChildIsPrimarySchoolAge" => new ChildIsPrimarySchoolAge($offsetDate, null),
                ],
                'notices' => [
                    "ChildIsAlmostOne" => new ChildIsAlmostOne($offsetDate, 0),
                    "ChildIsAlmostPrimarySchoolAge" => new ChildIsAlmostPrimarySchoolAge($offsetDate, 0),
                    "ChildIsAlmostSecondarySchoolAge" => new ChildIsAlmostSecondarySchoolAge($offsetDate, null),
                ],
                'relations' => [],
                'disqualifiers' => [
                    "ChildIsPrimarySchoolAge" => new ChildIsPrimarySchoolAge($offsetDate, 0),
                    "ChildIsSecondarySchoolAge" => new ChildIsSecondarySchoolAge($offsetDate, null)
                ],
            ],
            "App\Family" => [
                'credits' => [
                    "FamilyIsPregnant" => new FamilyIsPregnant($offsetDate, 3)
                ],
                'notices' => [
                    "FamilyHasUnverifiedChildren" =>new FamilyHasUnverifiedChildren($offsetDate, null),
                ],
                'disqualifiers' => [
                    "FamilyHasNoEligibleChildren" =>new FamilyHasNoEligibleChildren($offsetDate, null),
                ],
                'relations' => ['children'],
            ],
            "App\Registration" => [
                'credits' => [],
                'notices' => [],
                'relations' => ['family'],
            ],
        ];

        // Iterate over the modEvaluations and replace/add them
        foreach ($modEvaluations as $mod) {
            // Check we can
            if (class_exists($mod->entity) &&
                class_exists($mod->name)
            ) {
                $config = [
                    $mod->entity => [
                        $mod->purpose => [
                            // Calling the string to instantiate a class that exists
                            $mod->name => new $mod["name"]($offsetDate, $mod->value)
                        ]
                    ]
                ];
                $evaluations = array_replace_recursive($evaluations, $config);
            }
        }
        return $evaluations;
    }
}