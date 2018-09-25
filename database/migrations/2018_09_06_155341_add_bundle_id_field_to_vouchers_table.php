<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBundleIdFieldToVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers',function (Blueprint $table){
           $table->integer('bundle_id')->unsigned()->after('id')->nullable();
           $table->foreign('bundle_id')->references('id')->on('bundles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign('bundles_bundle_id_foreign');
            $table->dropColumn('bundle_id');
        });
    }
}
