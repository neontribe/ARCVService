<?php



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
        Schema::table('voucher_states', static function (Blueprint $table) {
            $table->integer('user_id')
                ->unsigned()
                ->change();

            $table->string('user_type')
                ->after('user_id')
                ->default("")
                ->change();
        });
    }
}
