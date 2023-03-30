<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', static function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('vouchers');

            if (!array_key_exists("vouchers_code_index", $indexesFound)) {
                $table->index([DB::raw('code(10)')], 'vouchers_code_index');
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
    public function down()
    {
        Schema::table('vouchers', static function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('vouchers');

            if (array_key_exists("vouchers_code_index", $indexesFound)) {
                $table->dropIndex("vouchers_code_index");
            }

            if (array_key_exists("vouchers_currentstate_index", $indexesFound)) {
                $table->dropIndex("vouchers_currentstate_index");
            }
        });
    }
};
