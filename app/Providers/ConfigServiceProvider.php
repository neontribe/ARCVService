<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{

    /
    private $config;
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->config = app('config');
        $this->config->set('session.domain', );
        $this->config->set('session.cookie', );
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

    private function getAppNameByHost()
    {
        switch (Request::getHost()) {
            case $this->config('arc.service_domain'):
                return "arcv_service";
                break;
            case $this->config('arc.store_domain'):
                break;
            default :
                abort(500);
        }
    }
}
