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
            $table->integer('assignee_id')->unsigned()->nullable(); // who was assigned this voucher.
            $table->integer('creditor_id')->unsigned()->nullable(); // who's owed money for this.
            $table->string('code', 32); // the actual voucher code.
            $table->string('currentstate', 24)->default('requested');
            $table->integer('sponsor_id')->unsigned();  // the organisation that sponsored this token (usually an LA).
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreign('creditor_id')
                ->references('id')
                ->on('traders');

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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['creditor_id']);
            $table->dropForeign(['sponsor_id']);
        });
        Schema::dropIfExists('vouchers');
    }
}
