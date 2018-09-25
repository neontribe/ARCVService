<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entitlement')->unsigned(); // for recording actual entitlement when issued
            $table->integer('family_id')->unsigned(); // FK Families
            $table->integer('centre_id')->unsigned(); // FK Centres
            $table->timestamps(); //created at and updated at
            $table->timestamp('allocated_at')->nullable();

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
        Schema::dropIfExists('bundles');
    }
}
