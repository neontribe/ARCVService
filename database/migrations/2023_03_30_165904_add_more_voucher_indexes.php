<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', static function (Blueprint $table) {
            $indexesFound = Arr::pluck(Schema::getIndexes('vouchers'), 'name');

            if (!array_key_exists("vouchers_code_index", $indexesFound)) {
                $table->index('code', 'vouchers_code_index');
            }

            if (!array_key_exists("vouchers_currentstate_index", $indexesFound)) {
                $table->index('currentstate', 'vouchers_currentstate_index');
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
            $indexesFound = Arr::pluck(Schema::getIndexes('vouchers'), 'name');

            if (array_key_exists("vouchers_code_index", $indexesFound)) {
                $table->dropIndex("vouchers_code_index");
            }

            if (array_key_exists("vouchers_currentstate_index", $indexesFound)) {
                $table->dropIndex("vouchers_currentstate_index");
            }
        });
    }
};
