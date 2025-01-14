<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RemoveExcessReminderFieldsFromRegistrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Cannot bulk drop multiple tables with SQLite (tests).
        Schema::table('registrations', static function ($table) {
            $table->dropColumn(['fm_chart_on', 'fm_diary_on', 'fm_privacy_on']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('registrations', static function ($table) {
            $table->dateTime('fm_chart_on')->nullable();
            $table->dateTime('fm_diary_on')->nullable();
            $table->datetime('fm_privacy_on')->nullable();
        });
    }
}
