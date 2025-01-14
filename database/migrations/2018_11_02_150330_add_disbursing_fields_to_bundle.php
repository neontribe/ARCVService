<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisbursingFieldsToBundle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // We can drop the allocating_centre, it's not a thing
        Schema::table('bundles', static function (Blueprint $table) {
            $table->dropForeign(['allocating_centre_id']);
            $table->dropColumn('allocating_centre_id');
        });

        Schema::table('bundles', static function (Blueprint $table) {

            $table->integer('disbursing_user_id')->after('disbursing_centre_id')->unsigned()->nullable();

            $table->integer('collecting_carer_id')->after('registration_id')->unsigned()->nullable();

            $table->foreign('disbursing_user_id')->references('id')->on('centre_users');

            $table->foreign('collecting_carer_id')->references('id')->on('carers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bundles', static function (Blueprint $table) {
            $table->integer('allocating_centre_id')->after('registration_id')->unsigned()->nullable();

            $table->foreign('allocating_centre_id')->references('id')->on('carers');
        });


        Schema::withoutForeignKeyConstraints(static function () {
            Schema::table('bundles', static function (Blueprint $table) {
                $table->dropForeign(['disbursing_user_id']);
                $table->dropForeign(['collecting_carer_id']);
                $table->dropColumn(['disbursing_user_id', 'collecting_carer_id']);
            });
        });
    }
}
