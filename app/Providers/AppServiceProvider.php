<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
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

        // Extend Builder to add a new sub-querys
        Builder::macro('orderBySub', function (Builder $query, $direction = 'asc') {
            // Prevents passing sql as the "direction" component.
            $direction = (in_array($direction, ['asc', 'desc', '']))
                ? $direction
                : 'asc';
            return $this->orderByRaw("({$query->limit(1)->toSql()}) {$direction}");
        });

        Builder::macro('orderBySubDesc', function (Builder $query) {
            return $this->orderBySub($query, 'desc');
        });

        // Needed because we're still serialising cookies!
        Passport::withCookieSerialization();


        // adds "push once"
        Blade::directive('pushonce', static function ($expression) {
            [$pushName, $pushSub] = explode(':', trim(substr($expression, 1, -1)));

            $key = '__pushonce_'.str_replace('-', '_', $pushName).'_'.str_replace('-', '_', $pushSub);

            return "<?php if(! isset(\$__env->{$key})): \$__env->{$key} = 1; \$__env->startPush('{$pushName}'); ?>";
        });
        Blade::directive('endpushonce', static function ($expression) {
            return '<?php $__env->stopPush(); endif; ?>';
        });
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
