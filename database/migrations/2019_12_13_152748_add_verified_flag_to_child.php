<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVerifiedFlagToChild extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('children', static function (Blueprint $table) {
            $table->boolean('verified')
                ->nullable()
                ->default(null)
                ->after('born');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('children', static function (Blueprint $table) {
            $table->dropColumn('verified');
        });
    }
}
