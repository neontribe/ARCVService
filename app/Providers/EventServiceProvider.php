<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\VoucherHistoryEmailRequested' => [
            'App\Listeners\SendVoucherHistoryEmail',
        ],
        'App\Events\VoucherPaymentRequested' => [
            'App\Listeners\SendVoucherPaymentRequestEmail',
        ],
        'Illuminate\Auth\Events\Authenticated' => [
            'App\Listeners\CentreUserAuthenticated',
        ],
        \SM\Event\SMEvents::POST_TRANSITION => [
            'App\Listeners\StateHistoryManager@postTransition',
        ],
        \SM\Event\SMEvents::PRE_TRANSITION => [
            'App\Listeners\StateHistoryManager@preTransition',
        ],
        \SM\Event\SMEvents::TEST_TRANSITION => [
            'App\Listeners\StateHistoryManager@testTransition',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
