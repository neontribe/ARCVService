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
    'uses' => 'Service\Auth\LoginController@showLoginForm',
]);
Route::post('login', 'Service\Auth\LoginController@login');

Route::get('/', 'Service\AdminController@index')->name('admin.dashboard');

// Admin (Service) Password Reset Routes...
Route::get('password/reset', 'Service\Auth\ForgotPasswordController@showLinkRequestForm')
    ->name('admin.password.request')
;
Route::post('password/email', 'Service\Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('admin.password.email')
;
Route::get('password/reset/{token}', 'Service\Auth\ResetPasswordController@showResetForm')
    ->name('admin.password.reset')
;
Route::post('admin.password/reset', 'Service\Auth\ResetPasswordController@reset');

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
