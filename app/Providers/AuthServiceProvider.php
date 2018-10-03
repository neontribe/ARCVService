<?php

namespace App\Providers;

use App\Registration;
use App\Trader;
use App\Voucher;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Trader::class => \App\Policies\Api\TraderPolicy::class,
        Voucher::class => \App\Policies\Api\VoucherPolicy::class,
        Registration::class => \App\Policies\Store\RegistrationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes(function ($router) {
            $router->forAccessTokens();
            $router->forTransientTokens();
        });

        Passport::tokensExpireIn(Carbon::now()->addHours(24));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(7));
    }
}
