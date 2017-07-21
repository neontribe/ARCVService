<?php

use Illuminate\Http\Request;

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
        ]);

        Route::get('traders/{trader}/vouchers', [
            'as' => 'api.trader.vouchers',
            'uses' => 'TraderController@showVouchers',
        ]);

        Route::get('traders/{trader}/voucher-history', [
            'as' => 'api.trader.voucher-history',
            'uses' => 'TraderController@showVoucherHistory',
        ]);

        Route::post('traders/{trader}/voucher-history-email', [
            'as' => 'api.trader.voucher-history-email',
            'uses' => 'TraderController@emailVoucherHistory',
        ]);
    });

    Route::post('vouchers', [
        'as' => 'api.voucher.transition',
        'uses' => 'VoucherController@transition',
    ])->middleware('can:collect,App\Voucher');
});
