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

Route::get('login/refresh', [
    'as' => 'api.login.refresh',
    'uses' => 'Auth\LoginController@refresh',
]);

/** Authentication required --------------------------------------------- */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('logout', [
    'as' => 'api.logout',
    'uses' => 'Auth\LoginController@logout',
]);

Route::get('traders/{trader}/vouchers', [
    'as' => 'api.trader.vouchers',
    'uses' => 'TraderController@showVouchers',
]);

Route::post('vouchers', [
    'as' => 'api.voucher.collect',
    'uses' => 'VoucherController@collect',
]);
