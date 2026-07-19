<?php

use App\AdminUser;
use App\CentreUser;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private array $typeMap = [
        '' => User::class,
        'User' => User::class,
        'AdminUser' => AdminUser::class,
        'CentreUser' => CentreUser::class,
    ];

    public function up(): void
    {
        // Repair user_type values before dropping the FK
        foreach ($this->typeMap as $from => $to) {
            DB::table('voucher_states')
                ->where('user_type', $from)
                ->update(['user_type' => $to]);
        }

        // drop the foreign key constraint.
        Schema::table('voucher_states', static function (Blueprint $table) {
//            $table->dropForeign(['user_id']);
        });
    }

    public function down(): void
    {
        // Reverse the user_type repairs; if it rolls back it will replace the '' with User, which is fine.
        foreach (array_reverse($this->typeMap) as $from => $to) {
            DB::table('voucher_states')
                ->where('user_type', $to)
                ->update(['user_type' => $from]);
        }
        // we can't replace the foreign key, there may be non-user id's in the user_id field.
    }
};
