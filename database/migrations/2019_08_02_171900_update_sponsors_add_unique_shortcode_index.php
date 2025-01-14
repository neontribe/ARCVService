<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSponsorsAddUniqueShortcodeIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('sponsors', static function (Blueprint $table) {
            $table->unique('shortcode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('sponsors', static function (Blueprint $table) {
            $table->dropUnique('sponsors_shortcode_unique');
        });
    }
}
