<?php

/*
|--------------------------------------------------------------------------
| API Routes for ARCVMarket App
|--------------------------------------------------------------------------
*/

Route::post('login', [
    'as' => 'api.login',
    'uses' => 'Auth\LoginController@login',
]);

Route::post('login/refresh', [
    'as' => 'api.login.refresh',
    'uses' => 'Auth\LoginController@refresh',
]);

Route::post('user/lost_password', [
    'as' => 'api.user.lost_password',
    'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail'
]);

Route::post('user/lost_password/reset', [
    'as' => 'api.user.reset_password',
    'uses' => 'Auth\ResetPasswordController@reset'
]);

/** Authentication required --------------------------------------------- */

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('queue/{jobStatus}', [
        'as' => 'api.queued-task.show',
        'uses' => 'QueueController@show'
    ]);

    Route::post('logout', [
        'as' => 'api.logout',
        'uses' => 'Auth\LoginController@logout',
    ]);

    Route::get('traders', [
        'as' => 'api.traders',
        'uses' => 'TraderController@index',
    ]);

    Route::group(['middleware' => 'can:view,trader'], function () {
        Route::get('traders/{trader}', [
            'as' => 'api.trader',
            'uses' => 'TraderController@show',
            // $user and App\Trader sent implicitly to policy.
        ])->where('trader', '^[0-9]+$');

        Route::get('traders/{trader}/vouchers', [
            'as' => 'api.trader.vouchers',
            'uses' => 'TraderController@showVouchers',
        ])->where('trader', '^[0-9]+$');

        Route::get('traders/{trader}/voucher-history', [
            'as' => 'api.trader.voucher-history',
            'uses' => 'TraderController@showVoucherHistory',
        ])->where('trader', '^[0-9]+$');

        Route::post('traders/{trader}/voucher-history-email', [
            'as' => 'api.trader.voucher-history-email',
            'uses' => 'TraderController@emailVoucherHistory',
        ])->where('trader', '^[0-9]+$');
    });

    /**
     * Legacy transition for old clients
     */
    Route::post('vouchers', [
        'as' => 'api.voucher.transition',
        'uses' => 'VoucherController@legacyTransition',
    ])->middleware('can:collect,App\Voucher');

    /**
     * new voucher transition routes
     */
    Route::post('vouchers/transitions', [
        'as' => 'api.vouchers.transition-responses.store',
        'uses' => 'TransitionController@store',
    ])->middleware('can:collect,App\Voucher');

    Route::get('vouchers/transitions/{jobStatus}', [
        'as' => 'api.vouchers.transition-response.show',
        'uses' => 'TransitionController@show',
    ])->where('jobStatus', '^[0-9]+$')
        //->middleware('can:collect,App\Voucher');
        ;

});
