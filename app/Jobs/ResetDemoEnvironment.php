<?php

namespace App\Jobs;

use App\Services\EnvWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetDemoEnvironment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(EnvWriter $envWriter): void
    {
        Artisan::call('migrate:refresh', ['--seed' => true, '--force' => true]);

        Artisan::call('passport:client', [
            '--password' => true,
            '--name'     => 'Rose Vouchers Password Grant Client',
            '--provider' => 'users',
        ]);

        $newSecret = DB::table('oauth_clients')->where('id', 1)->pluck('secret')[0];
        $envWriter->updateKey('PASSWORD_CLIENT_SECRET', $newSecret);
    }
}
