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
            $table->integer('trader_id')->unsigned()->nullable(); // who's owed money for this.
            $table->string('code', 32); // the actual voucher code.
            $table->string('currentstate', 24)->default('requested');
            $table->integer('sponsor_id')->unsigned();  // the organisation that sponsored this token (usually an LA).
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreign('trader_id')
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
            $table->dropForeign(['trader_id']);
            $table->dropForeign(['sponsor_id']);
        });
        Schema::dropIfExists('vouchers');
    }
}
