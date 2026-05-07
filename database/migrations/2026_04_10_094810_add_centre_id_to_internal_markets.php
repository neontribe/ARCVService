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
        Schema::table('markets', static function (Blueprint $table) {
            $table->unsignedInteger('centre_id')->nullable()->after('sponsor_id');
            $table->foreign('centre_id')->references('id')->on('centres')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(static function () {
            Schema::table('markets', static function (Blueprint $table) {
                $table->dropForeign(['centre_id']);
                $table->dropColumn('centre_id');
            });
        });
    }
};
