<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveOrderedPrintedFromVoucherStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_states', function (Blueprint $table) {
            // We only want to remove this data to production environments.
            // Seeded environments will not contain these deprecated states.
            if (!App::environment('production')) {
                return;
            }

            // I guess nice to have a record of how many rows were deleted?
            $number_to_delete = App\VoucherState::whereIn('to', ['ordered', 'printed'])->count();
            App\VoucherState::whereIn('to', ['ordered', 'printed'])->delete();
            Log::info($number_to_delete . ' printed and ordered voucher_states records deleted on migration.');

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
            // We have destroyed the records. If we need them back, grab from pre-deploy sql dump.
            // We could alternatively reconstruct them based on the voucher created_at.
            // This would include some moving around of data since at least 185000 vouchers
            // were missing these states - and we need ids to be same order as timestamps.
            return;
        });
    }
}
