<?php

namespace App\Providers;

use App\Views\Composers\PaymentsComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Laravel\Passport\Passport;

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

        Paginator::useBootstrap();

        // Needed because we're still serialising cookies!
        Passport::withCookieSerialization();


        // adds "push once"
        Blade::directive('pushonce', static function ($expression) {
            [$pushName, $pushSub] = explode(':', trim(substr($expression, 1, -1)));

            $key = '__pushonce_' . str_replace('-', '_', $pushName) . '_' . str_replace('-', '_', $pushSub);

            return "<?php if(! isset(\$__env->{$key})): \$__env->{$key} = 1; \$__env->startPush('{$pushName}'); ?>";
        });
        Blade::directive('endpushonce', static function ($expression) {
            return '<?php $__env->stopPush(); endif; ?>';
        });

        View::composer('*', PaymentsComposer::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // manual registration of non-auto-discovered packages
    }
}
