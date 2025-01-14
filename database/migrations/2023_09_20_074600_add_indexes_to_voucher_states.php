<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->index('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //        Schema::table('voucher_states', function (Blueprint $table) {
        //            // Remove the index from the 'email' column if needed
        //            $table->dropIndex('voucher_states_created_at_index');
        //        });
        //        Schema::table('voucher_states', function (Blueprint $table) {
        //            // Remove the index from the 'email' column if needed
        //            $table->dropIndex('voucher_states_voucher_id_index');
        //        });
    }
};
