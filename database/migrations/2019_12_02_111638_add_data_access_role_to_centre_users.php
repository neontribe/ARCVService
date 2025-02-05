<?php



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataAccessRoleToCentreUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('centre_users', static function (Blueprint $table) {
            $table->boolean('downloader')->default(false)->after('role');
        });

        // Update the table to auto-set the admin users.
        if (config('app.env') === 'production') {
            DB::update("UPDATE centre_users SET downloader = 1 WHERE role = 'foodmatters_user'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('centre_users', static function (Blueprint $table) {
            $table->dropColumn('downloader');
        });
    }
}
