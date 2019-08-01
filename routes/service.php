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
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::group(['middleware' => 'auth:admin'], function () {
    Route::get('vouchers', [
        'as' =>'admin.vouchers.index',
        'uses' => 'Admin\VouchersController@index',
    ]);
    Route::get('vouchers/create', [
        'as' =>'admin.vouchers.create',
        'uses' => 'Admin\VouchersController@create',
    ]);
    Route::get('workers', [
        'as' =>'admin.workers.index',
        'uses' => 'Admin\WorkersController@index',
    ]);
    Route::get('workers/create', [
        'as' =>'admin.workers.create',
        'uses' => 'Admin\WorkersController@create',
    ]);
    Route::get('workers/edit', [
        'as' =>'admin.workers.edit',
        'uses' => 'Admin\WorkersController@create',
    ]);
    Route::get('centres', [
        'as' =>'admin.centres.index',
        'uses' => 'Admin\CentresController@index',
    ]);
    Route::get('sponsors', [
        'as' =>'admin.sponsors.index',
        'uses' => 'Admin\SponsorsController@index',
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
