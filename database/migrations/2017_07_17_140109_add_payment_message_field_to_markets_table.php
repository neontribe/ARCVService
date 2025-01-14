<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddPaymentMessageFieldToMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('markets', static function ($table) {
            $table->string('payment_message')->default("");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('markets', static function ($table) {
            $table->dropColumn('payment_message');
        });
    }
}
