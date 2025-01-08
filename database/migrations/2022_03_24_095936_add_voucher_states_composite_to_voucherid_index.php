<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
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
            $indexesFound = Arr::pluck(Schema::getIndexes('voucher_states'), 'name');

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
            $indexesFound = Arr::pluck(Schema::getIndexes('voucher_states'), 'name');

            if (array_key_exists("voucher_states_to_voucher_id_index", $indexesFound)) {
                $table->dropIndex("voucher_states_to_voucher_id_index");
            }
        });
    }
}
