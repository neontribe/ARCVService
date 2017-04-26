<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoucherStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_states', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transition');
            $table->integer('prev_id')->unsigned()->nullable(); // Previous event in record (if any)
            $table->integer('next_id')->unsigned()->nullable(); // Next event in record (if any)
            $table->string('from')->nullable(); // What we were before this event.
            $table->integer('user_id')->unsigned(); // a User who made this happen.
            $table->integer('voucher_id')->unsigned();
            $table->string('to');   // What state we are now.
            $table->string('source'); // Where it was done from.

            $table->timestamps(); // captures "When"
        });

        Schema::table('voucher_states', function (Blueprint $table) {

            $table->foreign('prev_id')
                ->references('id')
                ->on('voucher_states');

            $table->foreign('next_id')
                ->references('id')
                ->on('voucher_states');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('voucher_id')
                ->references('id')
                ->on('vouchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voucher_states', function (Blueprint $table) {

            $table->dropForeign(['prev_id']);
            $table->dropForeign(['next_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['voucher_id']);
        });
        Schema::dropIfExists('voucher_states');
    }
}
