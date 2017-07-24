<?php

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'auth:admin'], function () {
    Route::get('vouchers', [
        'as' =>'admin.vouchers.index',
        'uses' => 'Admin\VouchersController@index',
    ]);
    Route::get('vouchers/create', [
        'as' =>'admin.vouchers.create',
        'uses' => 'Admin\VouchersController@create',
    ]);
    Route::post('vouchers', [
        'as' =>'admin.vouchers.storebatch',
        'uses' => 'Admin\VouchersController@storeBatch',
    ]);
    Route::post('logout', [
        'as' =>'admin.logout',
        'uses' => 'Auth\LoginController@logout',
    ]);
});
