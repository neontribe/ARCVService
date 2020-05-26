<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sponsor_id');
            $table->string('name');
            $table->string('entity');
            $table->string('purpose');
            $table->integer('value')->nullable();
            $table->timestamps();
            $table->unique(['sponsor_id','name'], 'unique_sponsor_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('evaluations')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropUnique('unique_sponsor_name');
            });
            Schema::drop('evaluations');
        }
    }
}
