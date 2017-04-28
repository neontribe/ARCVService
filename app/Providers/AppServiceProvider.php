<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fix for MySQL < v5.7.7 and MariaDB environs.
        // Recommended at https://laravel-news.com/laravel-5-4-key-too-long-error/
        Schema::defaultStringLength(191);
   }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            $this->app->register('Laracasts\Generators\GeneratorsServiceProvider');
        }
    }
}
