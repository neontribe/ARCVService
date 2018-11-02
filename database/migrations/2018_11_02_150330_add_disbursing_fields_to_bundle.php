<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisbursingFieldsToBundle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->integer('disbursing_user_id')
                ->after('registration_id')
                ->unsigned()
                ->nullable();

            $table->integer('collecting_carer_id')
                ->after('registration_id')
                ->unsigned()
                ->nullable();

            $table->foreign('disbursing_user_id')
                ->references('id')
                ->on('centre_users');

            $table->foreign('collecting_carer_id')
                ->references('id')
                ->on('carers');

            // We can drop the allocating_centre, it's not a thing
            Schema::table('bundles', function (Blueprint $table) {
                $table->dropForeign('bundles_allocating_centre_id_foreign');
                $table->dropColumn('allocating_centre_id');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropForeign('bundles_disbursing_user_id_foreign');
            $table->dropColumn('disbursing_user_id');

            $table->dropForeign('bundles_collecting_carer_id_foreign');
            $table->dropColumn('collecting_carer_id');
        });
    }
}
