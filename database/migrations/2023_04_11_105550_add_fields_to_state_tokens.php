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
        Schema::table('state_tokens', function (Blueprint $table) {
              $table->integer('user_id')->unsigned()->after('uuid')->nullable();
              $table->integer('admin_user_id')->unsigned()->after('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('state_tokens', function (Blueprint $table) {
            $table->dropColumn(['user_id','admin_user_id']);
        });
    }
};
