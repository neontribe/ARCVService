<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCentresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('prefix', 5);
            $table->integer('sponsor_id')->unsigned(); //FK Sponsor
            $table->enum('print_pref', ['collection', 'individual'])->default('collection');
            $table->timestamps();

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
        Schema::dropIfExists('centres');
    }
}
