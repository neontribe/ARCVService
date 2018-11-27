<?php

use App\CentreUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCentreCentreUserPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('centre_centre_user', function (Blueprint $table) {
            $table->integer('centre_user_id')->unsigned()->index();
            $table->foreign('centre_user_id')->references('id')->on('centre_users')->onDelete('cascade');
            $table->integer('centre_id')->unsigned()->index();
            $table->foreign('centre_id')->references('id')->on('centres')->onDelete('cascade');
            $table->boolean('homeCentre')->default(false);
            $table->primary(['centre_user_id', 'centre_id']);
        });

        $this->migrateRelationships();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('centre_centre_user');
    }

    /**
     * Updates the relationships for all pre-existing CentreUsers to use this pivot.
     */
    public function migrateRelationships()
    {
        // Find CentreUsers with a Centre
        $centreUsers = CentreUser::whereNotNull('centre_id');

        /** @var CentreUser $centreUser */
        foreach ($centreUsers as $centreUser) {
            $centreUser->centres()->attach($centreUser->centre_id, ['homeCentre' => true]);
            $centreUser->save();
        }
    }
}
