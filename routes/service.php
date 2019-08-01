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
;.
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('admin.password.email')
;
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')
    ->name('admin.password.reset')
;
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::group(['middleware' => 'auth:admin'], function () {
    // Voucher Management
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

    // Worker Management
    Route::get('workers', [
        'as' =>'admin.centreusers.index',
        'uses' => 'Admin\CentreUsersController@index',
    ]);
    Route::get('workers/create', [
        'as' =>'admin.centreusers.create',
        'uses' => 'Admin\CentreUsersController@create',
    ]);
    Route::post('workers', [
        'as' =>'admin.centreusers.store',
        'uses' => 'Admin\CentreUsersController@store',
    ]);

    // Centre Management
    Route::get('centres', [
        'as' =>'admin.centres.index',
        'uses' => 'Admin\CentresController@index',
    ]);

    Route::get('centres/{id}/neighbors', [
        'as' => 'admin.centre_neighbors.index',
        'uses' => 'Admin\CentresController@getNeighborsAsJson'
    ]);

    // Sponsor Management
    Route::get('sponsors', [
        'as' =>'admin.sponsors.index',
        'uses' => 'Admin\SponsorsController@index',
    ]);
  
    Route::get('sponsors/create', [
        'as' =>'admin.sponsors.create',
        'uses' => 'Admin\SponsorsController@create',
    ]);
  
    Route::post('logout', [
        'as' =>'admin.logout',
        'uses' => 'Auth\LoginController@logout',
    ]);
});
