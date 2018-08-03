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
Route::get('login', [
    'as' => 'store.login',
    'uses' => 'Auth\LoginController@showLoginForm',
]);

Route::post('login', 'Auth\LoginController@login');

Route::post('logout', [
    'as' => 'store.logout',
    'uses' => 'Auth\LoginController@logout',
]);

// Admin (Service) Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')
    ->name('store.password.request')
;
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('store.password.email')
;
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')
    ->name('store.password.reset')
;
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

// Service Dashboard route
// Default redirect to Service Dashboard

// TODO : use of singular/plurals in route names; Mixed opinions found. discuss.

Route::get('/', function () {
    $route = (Auth::check()) ? 'store.dashboard' : 'store.login';
    return redirect()->route($route);
})->name('store.base');

Route::group(['middleware' => 'auth:store'], function () {
    Route::get('dashboard', 'DashboardController@index')->name('store.dashboard');

    Route::resource('registration', 'RegistrationController', [
        'names' => [
            'index' => 'store.registration.index',
            'create' => 'store.registration.create',
            'edit' => 'store.registration.edit',
            'store' => 'store.registration.store',
            'update' => 'store.registration.update',
        ],
        'only' => [
            'index',
            'create',
            'edit',
            'store',
            'update',
        ],
    ]);

    // Update (deactivate) a Registration's Family
    Route::put('/registrations/{registration}/family', [
        'as' => 'store.registration.family',
        'uses' => 'FamilyController@update',
    ]);

    // Printables

    // Print a specific Family Form for User Centre (Edit page)
    Route::get('/registrations/{registration}/print', [
        'as' => 'store.registration.print',
        'uses' => 'RegistrationController@printOneIndividualFamilyForm',
    ]);

    // Batch print Family Forms for User Centre
    Route::get('/registrations/print', [
        'as' => 'store.registrations.print',
        'uses' => 'RegistrationController@printBatchIndividualFamilyForms',
    ]);

    // Print a Specific Centre's Registration's register form
    Route::get('/centres/{centre}/registrations/collection', [
        'as' => 'store.centre.registrations.collection',
        'uses' => 'CentreController@printCentreCollectionForm',
    ]);

    // ALL centres registrations as a summary spreadsheet
    Route::get('/centres/registrations/summary', [
        'as' => 'store.centres.registrations.summary',
        'uses' => 'CentreController@exportRegistrationsSummary',
    ])->middleware(['can:export,App\Registration']);
});
