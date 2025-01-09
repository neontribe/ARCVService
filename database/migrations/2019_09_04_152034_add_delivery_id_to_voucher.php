<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryIdToVoucher extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', static function (Blueprint $table) {
            $table->integer('delivery_id')->unsigned()->after('bundle_id')->nullable();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('delivery_id')->references('id')->on('deliveries');
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
        Schema::table('vouchers', static function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['delivery_id']);
            }
            $table->dropColumn('delivery_id');
        });
    }
}
