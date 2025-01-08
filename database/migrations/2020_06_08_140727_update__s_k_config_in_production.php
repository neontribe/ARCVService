<?php



use Illuminate\Support\Facades\App;
use App\Evaluation;
use App\Sponsor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class UpdateSKConfigInProduction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Only run in production
        if (App::environment('production')) {
            // Fetch the SK area
            $sponsor = Sponsor::where('shortcode', 'SK')->first();
            if (!$sponsor) {
                // Log a warning
                Log::warning(self::class . ": up() - unable to find SK area");
                return;
            }

            // Changes for crediting primary schoolers if they are qualified.
            $modfyingEvaluations = [
                // warn when primary schoolers are approaching end of school
                new Evaluation([
                    "name" => "ChildIsAlmostSecondarySchoolAge",
                    "value" => "0",
                    "purpose" => "notices",
                    "entity" => "App\Child",
                ]),

                // credit primary schoolers
                new Evaluation([
                    "name" => "ChildIsPrimarySchoolAge",
                    "value" => "3",
                    "purpose" => "credits",
                    "entity" => "App\Child",
                ]),

                // don't disqualify primary schoolers
                new Evaluation([
                    "name" => "ChildIsPrimarySchoolAge",
                    "value" => null,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Child",
                ]),

                // disqualify secondary schoolers instead
                new Evaluation([
                    "name" => "ChildIsSecondarySchoolAge",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Child",
                ]),

                // Turn on disqualifier for primary school kids without younger siblings
                new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
                ]),
            ];

            // Add those to the sponsor
            $sponsor->evaluations()->saveMany($modfyingEvaluations);

            // Check operation
            if ($sponsor->evaluations()->count() < count($modfyingEvaluations)) {
                // Log failure
                Log::warning(self::class . ": up() - SK area still has no custom evaluations");
                return;
            }
            // Log success
            Log::info(self::class . ": ip() - SK area evaluations added");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Only run in production
        if (App::environment('production')) {
            // Fetch the SK area
            $sponsor = Sponsor::where('shortcode', 'SK')->first();
            if (!$sponsor) {
                // Log a warning
                Log::warning(self::class . ": down() - unable to find SK area");
                return;
            }

            // Remove those evaluations
            $sponsor->evaluations()->delete();

            // Check operation
            if ($sponsor->evaluations()->count() > 0) {
                // Log failure.
                Log::warning(self::class . ": down() - SK area still has custom evaluations");
                return;
            }
            // Log success
            Log::info(self::class . ": down() - SK area evaluations removed");
        }
    }
}
