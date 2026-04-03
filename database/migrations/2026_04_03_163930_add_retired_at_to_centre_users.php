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
        Schema::table('centre_users', static function (Blueprint $table) {
            $table->datetime('retired_at')->after('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centre_users', static function (Blueprint $table) {
            $table->dropColumn(['retired_at']);
        });
    }
};
