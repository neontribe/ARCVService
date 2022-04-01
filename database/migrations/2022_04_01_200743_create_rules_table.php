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
            $table->string('entity')->nullable();//Needs sorting
            $table->string('type')->nullable();//Needs sorting
            $table->integer('value')->default(0);
            $table->integer('min_year')->nullable();
            $table->integer('min_month')->nullable();
            $table->integer('max_year')->nullable();
            $table->integer('max_month')->nullable();
            $table->boolean('warning')->default(0);
            $table->boolean('has_prescription')->default(0);
            $table->integer('except_if_rule_id')->nullable();
            $table->boolean('family_exists')->default(0);
            $table->boolean('is_at_primary_school')->default(0);
            $table->boolean('is_at_secondary_school')->default(0);
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
        Schema::dropIfExists('rules');
    }
}
