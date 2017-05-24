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

    Route::get('traders/{trader}', [
        'as' => 'api.trader',
        'uses' => 'TraderController@show',
        // $user and App\Trader sent implicitly to policy.
    ])->middleware('can:view,trader');

    Route::get('traders/{trader}/vouchers', [
        'as' => 'api.trader.vouchers',
        'uses' => 'TraderController@showVouchers',
    ])->middleware('can:view,trader');

    Route::get('traders/{trader}/voucher-history', [
        'as' => 'api.trader.voucher-history',
        'uses' => 'TraderController@showVoucherHistory',
    ])->middleware('can:view,trader');

    Route::post('vouchers', [
        'as' => 'api.voucher.collect',
        'uses' => 'VoucherController@collect',
    ])->middleware('can:collect,App\Voucher');

});
