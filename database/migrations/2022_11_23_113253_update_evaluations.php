<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEvaluations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change ChildIsPrimarySchoolAge credits from 3 to 4 for all areas
        DB::update('UPDATE evaluations SET value=4 WHERE name=\'ChildIsPrimarySchoolAge\' AND purpose=\'credits\'');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverse back to 3 if need be
        DB::update('UPDATE evaluations SET value=3 WHERE name=\'ChildIsPrimarySchoolAge\' AND purpose=\'credits\'');
    }
}
