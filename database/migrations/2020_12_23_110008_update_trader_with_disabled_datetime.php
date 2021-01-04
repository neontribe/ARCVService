<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTraderWithDisabledDatetime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('traders', function (Blueprint $table) {
            $table->timestamp('disabled_at')
                ->after('deleted_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('traders', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
        });
    }
}
