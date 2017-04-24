<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->increments('id'); // a voucher *instance*.
            $table->integer('assigner_id')->unsigned()->nullable(); // who was assigned this voucher.
            $table->integer('redeemer_id')->unsigned()->nullable(); // who redeemed this with ARC.
            $table->integer('creditor_id')->unsigned()->nullable(); // who's owed money for this.
            $table->string('code', 32); // the actual voucher code.
            $table->string('state', 16);    // TODO sub out to a table at some point.
            $table->integer('sponsor_id')->unsigned();  // the organisation that sponsored this token (usually an LA).
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
