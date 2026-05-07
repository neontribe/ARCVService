<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carers', static function (Blueprint $table) {
            $table->text('emailsecret')->after('name')->nullable();
            $table->text('telnosecret')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carers', static function (Blueprint $table) {
            $table->dropColumn(['emailsecret', 'telnosecret']);
        });
    }
};
