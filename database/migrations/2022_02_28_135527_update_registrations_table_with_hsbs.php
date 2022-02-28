<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRegistrationsTableWithHsbs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('registrations', function (Blueprint $table) {
        $table->timestamp('eligible_from')->nullable()->before('created_at');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('registrations', function (Blueprint $table) {
          $table->dropColumn('eligible_from');
      });
    }
}
