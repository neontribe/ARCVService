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
            $table->integer('registration_id')->unsigned(); // FK Registrations
            $table->integer('allocating_centre_id')->unsigned(); // FK Centres
            $table->integer('disbursing_centre_id')->unsigned()->nullable(); // FK Centres
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps(); //created at and updated at

            $table->foreign('registration_id')
                ->references('id')
                ->on('registrations');

            $table->foreign('allocating_centre_id')
                ->references('id')
                ->on('centres');

            $table->foreign('disbursing_centre_id')
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
