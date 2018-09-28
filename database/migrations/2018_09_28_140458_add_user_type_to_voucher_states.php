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
                ->nullable()
                ->default(null);
        });


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
                'reject-to-allocated',
                'confirm'
            ) 
        ");

        DB::statement("
            UPDATE voucher_states SET user_type = 'CentreUser' 
            WHERE transition
            IN (
                'bundle',
                'disburse',
                'unbundle-to-printed',
                'unbundle-to-dispatched',
                'lose',
                'allocate'
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
