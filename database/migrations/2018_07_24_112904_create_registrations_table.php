<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('family_id')->unsigned();
            $table->integer('centre_id')->unsigned();
            $table->string('eligibility');
            $table->dateTime('consented_on')->nullable();
            $table->dateTime('fm_chart_on')->nullable();
            $table->dateTime('fm_diary_on')->nullable();
            $table->datetime('fm_privacy_on')->nullable();
            $table->timestamps();

            $table->foreign('family_id')
                ->references('id')
                ->on('families');

            $table->foreign('centre_id')
                ->references('id')
                ->on('centres');
        });
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registrations');
    }
}

