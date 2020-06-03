<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateTokenIdToVoucherStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_states', function (Blueprint $table) {
            $table->integer('state_token_id')
                ->after('to')
                ->unsigned()
                ->nullable()
            ;

            $table->foreign('state_token_id')
                ->references('id')
                ->on('state_tokens');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voucher_states', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('voucher_states_state_token_id_foreign');
            }
            $table->dropColumn('state_token_id');
        });
    }
}
