<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sponsor_id')->unsigned();
            $table->string('name')->default('New rule');
            $table->string('entity')->default('Child');//Needs sorting
            $table->string('type')->default('age');//Needs sorting
            $table->integer('value')->default(0);
            $table->boolean('warning')->default(0);
            $table->boolean('except_if_age')->default(0);
            $table->boolean('except_if_prescription')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rules');
    }
}
