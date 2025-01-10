<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraderUserPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('trader_user', static function (Blueprint $table) {
            $table->integer('trader_id')->unsigned()->index();
            $table->foreign('trader_id')->references('id')->on('traders')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['trader_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(static function () {
            Schema::table('trader_user', static function ($table) {
                $table->dropForeign(['trader_id']);
                $table->dropForeign(['user_id']);
            });
        });

        Schema::drop('trader_user');
    }
}
