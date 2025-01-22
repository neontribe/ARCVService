<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->foreign('market_id')->references('id')->on('markets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(static function () {
            Schema::table('traders', static function (Blueprint $table) {
                $table->dropForeign(['market_id']);
            });
        });
        Schema::dropIfExists('traders');
    }
}
