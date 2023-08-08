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
    public function register() : void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() : void
    {
        Mail::extend('mandrill', static function () {
            return (new MandrillTransportFactory)->create(
                new Dsn(
                    'mandrill+api', // give me the mandrill api transport
                    'default',
                    config('services.mandrill.key') // "user"
                )
            );
        });
    }
}
