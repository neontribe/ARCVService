<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name'); // Name of the market
            $table->string('location'); // Where it is.
            $table->integer('sponsor_id')->unsigned()->nullable(); // who's the primary sponsor here.
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('markets', function (Blueprint $table) {
            $table->foreign('sponsor_id')
                ->references('id')
                ->on('sponsors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropForeign(['sponsor_id']);
        });
        Schema::dropIfExists('markets');
    }
}
