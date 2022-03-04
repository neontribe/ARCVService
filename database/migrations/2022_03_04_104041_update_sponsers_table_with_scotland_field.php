<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSponsersTableWithScotlandField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('sponsors', function (Blueprint $table) {
        $table->boolean('is_scotland')->nullable()->default(false);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('sponsors', function (Blueprint $table) {
          $table->dropColumn('is_scotland');
      });
    }
}
