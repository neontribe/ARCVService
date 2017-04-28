<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('trader_id')->unsigned()->nullable();
            $table->string('currentstate', 24)->default('created');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreign('trader_id')
                ->references('id')
                ->on('traders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['trader_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('submissions');
    }
}
