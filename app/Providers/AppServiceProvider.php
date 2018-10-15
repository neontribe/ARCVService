<?php

namespace App\Providers;

use Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Laracasts\Generators\GeneratorsServiceProvider;
use Laravel\Dusk\DuskServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;

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

        //Custom ARC form valdiation rules
        Validator::extend('codeGreaterThan', function ($attribute, $value, $parameters, $validator) {
            $val = \App\Voucher::splitShortcodeNumeric($value);

            $otherVal = array_get(
                $validator->getData(),
                $parameters[0]
            );

            if (!empty($otherVal)) {
                $other = \App\Voucher::splitShortcodeNumeric($otherVal);
                Log::info("boom" . $val["number"] . "|" . $other("number"));
                return intval($val["number"]) > intval($other["number"]);
            } else {
                Log::info("boom");
                return false;
            }
        });

        Validator::replacer('codeGreaterThan', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':other', $parameters[0], $message);
        });

        Validator::extend('sameSponsor', function ($attribute, $value, $parameters, $validator) {
            $val = \App\Voucher::splitShortcodeNumeric($value);
            $other = \App\Voucher::splitShortcodeNumeric($parameters[0]);
            return $val['shortcode'] === $other['shortcode'];
        });

        Validator::replacer('sameSponsor', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':other', $parameters[0], $message);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(IdeHelperServiceProvider::class);
            $this->app->register(GeneratorsServiceProvider::class);
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
