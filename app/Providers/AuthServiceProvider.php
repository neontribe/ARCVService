<?php

namespace App\Providers;

use App\CentreUser;
use App\Registration;
use App\Policies\RegistrationPolicy;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
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
        \App\Trader::class => \App\Policies\Api\TraderPolicy::class,
        \App\Voucher::class => \App\Policies\Api\VoucherPolicy::class,
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

        //Authorisations

        // When a specific registration is requested
        Gate::define('view-registration', function (CentreUser $user, Registration $registration) {
            // Check the registration is for a centre relevant to the user.
            return $user->isRelevantCentre($registration->centre);
        });

        // When a specific registration is updated
        Gate::define('update-registration', function (CentreUser $user, Registration $registration) {
            // Check the registration is for a centre relevant to the user.
            return $user->isRelevantCentre($registration->centre);
        });

        // When a specific registration is printed individually
        Gate::define('print-registration', function (CentreUser $user, Registration $registration) {
            return $user->isRelevantCentre($registration->centre);
        });
    }
}
