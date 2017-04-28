<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// TODO: sort these out into proper PS-4 heirarchy or somtheing...
Route::get('vouchers/{$voucher}/batchredemption', 'VoucherController@batchRedeem');
Route::get('vouchers/{$voucher}/transitions', 'VoucherController@showTransitions');
Route::post('vouchers/{$voucher}/transitions/redemption', 'VoucherController@redeem');
Route::resource('vouchers', 'API\VoucherController', [
    'only' => ['index','show',]
]);


Route::resource('users', 'API\UserController', [
    'only' => ['index','show',]
]);

Route::resource('markets', 'API\MarketController', [
    'only' => ['index','show',]
]);

Route::resource('traders', 'API\TraderController', [
    'only' => ['index','show',]
]);
