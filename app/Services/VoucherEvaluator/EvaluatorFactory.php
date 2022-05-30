<?php

namespace App\Services\VoucherEvaluator;

use App\Registration;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostOne;
use App\Services\VoucherEvaluator\Evaluations\ChildIsAlmostPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsBetweenOneAndPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\ChildIsUnderOne;
use App\Services\VoucherEvaluator\Evaluations\FamilyIsPregnant;
use App\Services\VoucherEvaluator\Evaluators\VoucherEvaluator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EvaluatorFactory
{
    private static $registration;
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
        self::$registration = $registration;
        $evaluations = $registration->centre->sponsor->evaluations;
        return self::make($evaluations, $offsetDate);
    }

    /**
     * Combines the standard evaluations with specific modifications.
     *
     * @param Collection $modifiers
     * @param Carbon $offsetDate
     * @return array
     */
    public static function generateEvaluations(Collection $modifiers, Carbon $offsetDate)
    {
        $evaluations = [
            "App\Child" => [
                'credits' => [
                    "ChildIsUnderOne" => new ChildIsUnderOne($offsetDate, 6),
                    "ChildIsBetweenOneAndPrimarySchoolAge" => new ChildIsBetweenOneAndPrimarySchoolAge($offsetDate, 3),
                ],
                'notices' => [
                    "ChildIsAlmostOne" => new ChildIsAlmostOne($offsetDate, 0),
                    "ChildIsAlmostPrimarySchoolAge" => new ChildIsAlmostPrimarySchoolAge($offsetDate, 0),
                ],
                'relations' => [],
                'disqualifiers' => [
                    "ChildIsPrimarySchoolAge" => new ChildIsPrimarySchoolAge($offsetDate, 0),
                ],
            ],
            "App\Family" => [
                'credits' => [
                    "FamilyIsPregnant" => new FamilyIsPregnant($offsetDate, 3)
                ],
                'notices' => [
                ],
                'disqualifiers' => [],
                'relations' => ['children'],
            ],
            "App\Registration" => [
                'credits' => [],
                'notices' => [],
                'disqualifiers' => [],
                'relations' => ['family'],
            ],
        ];

        // the namespace for making fully qualified Evaluation classes
        $namespace = "App\Services\VoucherEvaluator\Evaluations";

        // Iterate over the modifying evaluations and replace/add/remove on the default template.
        foreach ($modifiers as $modifier) {
            $className = $namespace . '\\' . $modifier->name;
            // Check we have the correct, existing class
            if (class_exists($modifier->entity) &&
                class_exists($className)
            ) {
                // If this is an enabled rule
                if (!is_null($modifier->value)) {
                    $config = [
                        $modifier->entity => [
                            $modifier->purpose => [
                                // Calling the string to instantiate a class that exists
                                $modifier->name => new $className($offsetDate, $modifier->value)
                            ]
                        ]
                    ];
                    // merge it in.
                    $evaluations = array_replace_recursive($evaluations, $config);
                } else {
                    // this is a disabling rule, remove it, if it exists.
                    if (isset($evaluations[$modifier->entity][$modifier->purpose][$modifier->name])) {
                        unset($evaluations[$modifier->entity][$modifier->purpose][$modifier->name]);
                    }
                }
            }
        }
        return $evaluations;
    }
}
