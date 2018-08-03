<?php

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

// Admin (Service) Authentication Routes...
Route::get('login', [
    'as' => 'admin.login',
    'uses' => 'Auth\LoginController@showLoginForm',
]);
Route::post('login', 'Auth\LoginController@login');

Route::get('/', 'AdminController@index')->name('admin.dashboard');

// Admin (Service) Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')
    ->name('admin.password.request')
;
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('admin.password.email')
;
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')
    ->name('admin.password.reset')
;
Route::post('admin.password/reset', 'Auth\ResetPasswordController@reset');

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
