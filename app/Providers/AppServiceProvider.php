<?php

namespace App\Providers;

use App\Services\EnvWriter;
use App\View\Composers\PaymentsComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Passport 12 doesn't enable this by default
        Passport::enablePasswordGrant();

        // Fix for MySQL < v5.7.7 and MariaDB environs.
        // Recommended at https://laravel-news.com/laravel-5-4-key-too-long-error/
        Schema::defaultStringLength(191);

        Paginator::useBootstrap();

        // Needed because we're still serialising cookies!
        Passport::withCookieSerialization();

        View::composer('*', PaymentsComposer::class);

        // Gates
        Gate::define('take-developer-actions', static function () {
            // permit if the application is debugging
            return config('app.debug') === true;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(EnvWriter::class, function () {
            return new EnvWriter(base_path('.env'));
        });
    }
}
