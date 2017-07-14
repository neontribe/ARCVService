<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Admin (Service) Authentication Routes...
Route::get('login', [
    'as' => 'admin.login',
    'uses' => 'Service\Auth\LoginController@showLoginForm',
]);
Route::post('login', 'Service\Auth\LoginController@login');

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
