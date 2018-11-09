<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCentreUserCentrePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('centre_user_centre', function (Blueprint $table) {
            $table->integer('centre_user_id')->unsigned()->index();
            $table->foreign('centre_user_id')->references('id')->on('centre_users')->onDelete('cascade');
            $table->integer('centre_id')->unsigned()->index();
            $table->foreign('centre_id')->references('id')->on('centres')->onDelete('cascade');
            // this is a dodge don't set it false, set it null.
            $table->boolean('homeCentre')->nullable()->unique();
            $table->primary(['centre_user_id', 'centre_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('centre_user_centre');
    }
}
