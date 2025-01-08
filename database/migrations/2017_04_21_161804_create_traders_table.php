<?php



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('traders', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name'); // name of the trader
            $table->string('pic_url')->nullable(); // some kind of pic link
            $table->integer('market_id')->unsigned()->nullable();
            // Q: is it possible to have an unassigned trader?
            // Q: can a trader belong to many markets
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('traders', static function (Blueprint $table) {
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('traders', static function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['market_id']);
            }
        });
        Schema::dropIfExists('traders');
    }
}
