<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserTypeToVoucherStates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_states', function (Blueprint $table) {
            $table->string('user_type')
                ->after('user_id')
                ->default(""); // there needs to be a default, as this is not a null-able field.
        });

        // At time of creation, this is all the states we might see.
        DB::update("UPDATE voucher_states SET user_type = 'AdminUser' 
            WHERE transition
            IN (
                'order',
                'print',
                'dispatch',
                'payout',
                'expire',
                'retire'
            ) 
        ");

        DB::update("UPDATE voucher_states SET user_type = 'User' 
            WHERE transition
            IN (
                'collect',
                'reject-to-printed',
                'reject-to-dispatched',
                'confirm'
            ) 
        ");

        DB::statement("
            UPDATE voucher_states SET user_type = 'CentreUser' 
            WHERE transition
            IN (
                'lose'
            ) 
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voucher_states', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
}
