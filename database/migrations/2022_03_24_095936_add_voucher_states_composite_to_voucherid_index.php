<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoucherStatesCompositeToVoucheridIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_states', static function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('voucher_states');

            if (!array_key_exists("voucher_states_to_voucher_id_index", $indexesFound)) {
                $table->index(['to', 'voucher_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_states', static function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('voucher_states');

            if (array_key_exists("voucher_states_to_voucher_id_index", $indexesFound)) {
                $table->dropIndex("voucher_states_to_voucher_id_index");
            }
        });
    }
}
