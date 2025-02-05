<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCentresAddUniquePrefixIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('centres', static function (Blueprint $table) {
            $table->unique('prefix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('centres', static function (Blueprint $table) {
            $table->dropUnique('centres_prefix_unique');
        });
    }
}
