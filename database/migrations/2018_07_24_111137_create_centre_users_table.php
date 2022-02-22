<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCentreUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('centre_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('centre_user');
            $table->rememberToken();
            $table->integer('centre_id')->unsigned()->nullable();// Not all Users may have a CC e.g. Admins
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('centre_id')
                ->references('id')
                ->on('centres');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('centre_users');
    }
}
