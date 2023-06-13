<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carers', function (Blueprint $table) {
			$table->string('ethnicity')->nullable();
			$table->string('language')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carers', function (Blueprint $table) {
			$table->dropColumn('ethnicity');
			$table->dropColumn('language');
        });
    }
};
