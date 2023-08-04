<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class MandrillMailProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Mail::extend('mandrill', function () {
        return (new MandrillTransportFactory)->create(
            new Dsn(
                'mandrill+api',
                'default',
                config('services.mandrill.key')
            ));
        });
    }
}
