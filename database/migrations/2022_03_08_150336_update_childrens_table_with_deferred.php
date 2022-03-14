<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateChildrensTableWithDeferred extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('children', function (Blueprint $table) {
          $table->boolean('deferred')->default(false)->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('children', function (Blueprint $table) {
          $table->dropColumn('deferred');
      });
    }
}
