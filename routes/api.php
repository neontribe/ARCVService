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
    });

    Route::post('vouchers', [
        'as' => 'api.voucher.progress',
        'uses' => 'VoucherController@progress',
    ])->middleware('can:progress,App\Voucher');
});
