<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('registrations', function (Blueprint $table) {
        $table->string('eligibility_hsbs')->after('centre_id')->nullable();
        $table->string('eligibility_nrpf')->after('centre_id')->nullable();
        if (config('app.env') === 'production') {
          DB::update("UPDATE registrations SET eligibility_hsbs = 'healthy-start-applying'
              WHERE eligibility = 'healthy-start-applying'
          ");
          DB::update("UPDATE registrations SET eligibility_hsbs = 'healthy-start-receiving'
              WHERE eligibility = 'healthy-start-receiving'
          ");
          DB::update("UPDATE registrations SET eligibility_hsbs = 'healthy-start-receiving'
              WHERE eligibility = 'healthy-start'
          ");
          DB::update("UPDATE registrations SET eligibility_hsbs = 'healthy-start-receiving-not-eligible-or-rejected'
              WHERE eligibility = 'other'
          ");
          DB::update("UPDATE registrations SET eligibility_nrpf = 'Yes'
              WHERE eligibility = 'no-recourse-to-public-funds'
          ");
          DB::update("UPDATE registrations SET eligibility_nrpf = 'No'
              WHERE eligibility != 'no-recourse-to-public-funds'
          ");
        }
        $table->dropColumn('eligibility');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->string('eligibility');
        if (config('app.env') === 'production') {
          DB::update("UPDATE registrations SET eligibility = 'healthy-start-applying'
              WHERE eligibility_hsbs = 'healthy-start-applying'
          ");
          DB::update("UPDATE registrations SET eligibility = 'healthy-start-receiving'
              WHERE eligibility_hsbs = 'healthy-start-receiving'
          ");
          DB::update("UPDATE registrations SET eligibility = 'other'
              WHERE eligibility_hsbs = 'healthy-start-receiving-not-eligible-or-rejected'
          ");
          DB::update("UPDATE registrations SET eligibility = 'no-recourse-to-public-funds'
              WHERE eligibility_nrpf = 'Yes'
          ");
        }
        $table->dropColumn('eligibility_hsbs');
        $table->dropColumn('eligibility_nrpf');
    }
}
