<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubmissionStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_states', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transition');
            $table->string('from')->nullable(); // What we were before this event.
            $table->integer('user_id')->unsigned(); // a User who made this happen.
            $table->integer('submission_id')->unsigned();
            $table->string('to');   // What state we are now.
            $table->string('source'); // Where it was done from.
            $table->timestamps(); // captures "When"
        });

        Schema::table('submission_states', function (Blueprint $table) {

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('submission_id')
                ->references('id')
                ->on('submissions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_states', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['submission_id']);
        });
        Schema::dropIfExists('submission_states');
    }
}
