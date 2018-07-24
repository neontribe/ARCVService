<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFamiliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('families', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('initial_centre_id')->nullable()->unsigned(); // Where we registered first.
            $table->integer('centre_sequence')->nullable(); //Component for RVID.
            $table->dateTime('leaving_on')->nullable();
            $table->string('leaving_reason', 128)->nullable();
            $table->timestamps();

            $table->foreign('initial_centre_id')
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
        Schema::dropIfExists('families');
    }
}
