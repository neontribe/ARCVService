<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // TODO this could be neater, and less hard-coded.
        $config = app('config');
        // Set the cookie name
        switch (Request::getHost()) {
            case ($config->get('arc.service_domain')):
                $host = "arcv-service";
                break;
            case ($config->get('arc.store_domain')):
                $host = "arcv-store";
                break;
            default:
                $host = 'laravel_session';
        }
        $config->set('session.cookie', $host . "_session");
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
