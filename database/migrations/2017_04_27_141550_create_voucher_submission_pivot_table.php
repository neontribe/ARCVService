<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoucherSubmissionPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_submission', function (Blueprint $table) {
            $table->integer('submission_id')->unsigned()->index();
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
            $table->integer('voucher_id')->unsigned()->index();
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
            $table->primary(['submission_id', 'voucher_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('voucher_submission');
    }
}
