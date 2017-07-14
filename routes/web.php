<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Admin (Service) Authentication Routes...
Route::get('login', 'Service\Auth\LoginController@showLoginForm')
    ->name('login')
;
Route::post('login', 'Service\Auth\LoginController@login');
Route::post('logout', 'Service\Auth\LoginController@logout')
    ->name('logout')
;

// Admin (Service) Password Reset Routes...
Route::get('password/reset', 'Service\Auth\ForgotPasswordController@showLinkRequestForm')
    ->name('password.request')
;
Route::post('password/email', 'Service\Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('password.email')
;
Route::get('password/reset/{token}', 'Service\Auth\ResetPasswordController@showResetForm')
    ->name('password.reset')
;
Route::post('password/reset', 'Service\Auth\ResetPasswordController@reset');
