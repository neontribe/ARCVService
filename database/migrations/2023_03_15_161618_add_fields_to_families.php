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
        Schema::table('families', static function (Blueprint $table) {
            $table->timestamp('rejoin_on')->nullable();
            $table->integer('leave_amount')->default(0);
        });

        // Update the table to auto-set leave_amount for those who have previously left.
        if (config('app.env') === 'production') {
            DB::update("UPDATE families SET leave_amount = 1
                WHERE leaving_on IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('families', static function (Blueprint $table) {
            $table->dropColumn('rejoin_on');
        });
        Schema::table('families', static function (Blueprint $table) {
            $table->dropColumn('leave_amount');
        });
    }
};
