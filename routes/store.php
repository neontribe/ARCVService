<?php

// Admin (Store) Authentication Routes...
Route::get('login', [
    'as' => 'store.login',
    'uses' => 'Auth\LoginController@showLoginForm',
]);

Route::post('login', 'Auth\LoginController@login');

Route::post('logout', [
    'as' => 'store.logout',
    'uses' => 'Auth\LoginController@logout',
]);

// Admin (Store) Password Reset Routes...
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

// Store Dashboard route
// Default redirect to Service Dashboard

// TODO : use of singular/plurals in route names; Mixed opinions found. discuss.

Route::get('/', function () {
    $route = 'store.login';
    //$route = (Auth::guard('store')->check()) ? 'store.dashboard' : 'store.login';
    return redirect()->route($route);
})->name('store.base');

Route::group(['middleware' => 'auth:store'], function () {
    Route::get('dashboard', 'DashboardController@index')->name('store.dashboard');

    Route::resource('registrations', 'RegistrationController', [
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

    // Bundles

    // Need to write this route this way to use a policy.
    // Registration wasn't being passed through for a Gate based trick.

    Route::get(
        '/registrations/{registration}/voucher-manager',
        'BundleController@create'
    )
    ->name('store.registration.voucher-manager')
    ->middleware('can:view,registration');

    // puts (and replaces!) the currentBundle of vouchers!
    Route::put(
        '/registrations/{registration}/vouchers',
        'BundleController@update'
    )
    ->name('store.registration.vouchers')
    ->middleware('can:view,registration');

    Route::post(
        '/registrations/{registration}/vouchers',
        'BundleController@addVouchersToCurrentBundle'
    )
    ->name('store.registration.vouchers.post')
    ->middleware('can:view,registration');


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
