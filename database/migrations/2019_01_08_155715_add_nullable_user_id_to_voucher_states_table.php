<?php


use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableUserIdToVoucherStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->integer('user_id')
                ->unsigned()
                ->nullable()
                ->change();

            $table->string('user_type')
                ->nullable()
                ->after('user_id')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('voucher_states')
            ->whereNull('user_id')
            ->update(['user_id' => 0]);

        DB::table('voucher_states')
            ->whereNull('user_type')
            ->update(['user_type' => '']);

        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->integer('user_id')
                ->unsigned()
                ->default(0)
                ->change();

            $table->string('user_type')
                ->after('user_id')
                ->default("")
                ->change();
        });
    }
}
