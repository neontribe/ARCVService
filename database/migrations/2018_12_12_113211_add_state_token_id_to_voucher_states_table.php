<?php



use Illuminate\Support\Facades\DB;
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
    public function up(): void
    {
        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->integer('state_token_id')
                ->after('to')
                ->unsigned()
                ->nullable()
            ;

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('state_token_id')
                    ->references('id')
                    ->on('state_tokens');
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
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['state_token_id']);
            }
            $table->dropColumn('state_token_id');
        });
    }
}
