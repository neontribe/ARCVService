<?php

namespace App\Jobs;

use App\Services\EnvWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;

class ResetDemoEnvironment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(EnvWriter $envWriter, ClientRepository $clients): void
    {
        Artisan::call('migrate:refresh', ['--seed' => true, '--force' => true]);

        $client = $clients->createPasswordGrantClient(
            userId: null,
            name: 'Rose Vouchers Password Grant Client',
            redirect: '',
            provider: 'users',
        );

        $envWriter->updateKey('PASSWORD_CLIENT_SECRET', $client->plainSecret);
    }
}

