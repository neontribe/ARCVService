<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateHealthyStartEligibility extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Change any healthy-start folk to healthy-start-applying as the new normal.
        if (config('app.env') === 'production') {
            DB::update("UPDATE registrations SET eligibility = 'healthy-start-applying'
                WHERE eligibility = 'healthy-start'
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ...and reverse it. Don't run this on live after we've had user changes.
        if (config('app.env') === 'production') {
            DB::update("UPDATE registrations SET eligibility = 'healthy-start'
                WHERE eligibility = 'healthy-start-applying'
            ");
        }
    }
}
