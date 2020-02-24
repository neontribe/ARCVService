<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHealthyStartEligibility extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change any healthy-start folk to healthy-start-applying as the new normal.
        DB::update("UPDATE registrations SET eligibility = 'healthy-start-applying' 
            WHERE eligibility = 'healthy-start'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ...and reverse it. Don't run this on live after we've had user changes.
        DB::update("UPDATE registrations SET eligibility = 'healthy-start' 
            WHERE eligibility = 'healthy-start-applying' 
        ");
    }
}
