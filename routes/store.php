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
    ->where('token', '[0-9a-f]{64}')
;
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

// Store Dashboard route
// Default redirect to Service Dashboard

Route::get('/', function () {
    $route = 'store.login';
    //$route = (Auth::guard('store')->check()) ? 'store.dashboard' : 'store.login';
    return redirect()->route($route);
})->name('store.base');


// Store payment request link handling; does not require any auth
Route::get('/payment-request/{paymentUuid}', [
        'as' => 'store.payment-request.show',
        'uses' => 'PaymentController@show'
    ]);

Route::put('/payment-request/{paymentUuid}', [
        'as' => 'store.payment-request.update',
        'uses' => 'PaymentController@update'
    ]);


// Route groups for authorised routes
Route::group(['middleware' => 'auth:store'], function () {
    Route::get(
        'dashboard',
        'DashboardController@index'
    )->name('store.dashboard');

    // Route to update the CentreUser's Session
    Route::put('/session', [
        'as' => 'store.session.put',
        'uses' => 'SessionController@update'
    ]);

    Route::get('/registrations', [
        'as' => 'store.registration.index',
        'uses' => 'RegistrationController@index',
    ]);

    Route::post('/registrations', [
        'as' => 'store.registration.store',
        'uses' => 'RegistrationController@store',
    ]);

    Route::get('/registrations/create', [
        'as' => 'store.registration.create',
        'uses' => 'RegistrationController@create',
    ]);

    // Batch print Family Forms for User Centre
    Route::get('/registrations/print', [
        'as' => 'store.registrations.print',
        'uses' => 'RegistrationController@printBatchIndividualFamilyForms',
    ]);

    Route::group(
        // Must be able to access this registration
        ['middleware' => 'can:readOrUpdate,registration'],
        function () {

            // Edit a specific thing
            Route::get('/registrations/{registration}/edit', [
                'as' => 'store.registration.edit',
                'uses' => 'RegistrationController@edit',
            ])->where('registration', '^[0-9]+$');

            // Update a specific registration
            Route::put('/registrations/{registration}', [
                'as' => 'store.registration.update',
                'uses' => 'RegistrationController@update',
            ])->where('registration', '^[0-9]+$');

            // Update (deactivate) a specific Registration's Family
            Route::put('/registrations/{registration}/family', [
                'as' => 'store.registration.family',
                'uses' => 'FamilyController@update',
            ])->where('registration', '^[0-9]+$');

            // Print a specific registration
            Route::get('/registrations/{registration}/print', [
                'as' => 'store.registration.print',
                'uses' => 'RegistrationController@printOneIndividualFamilyForm',
            ])->where('registration', '^[0-9]+$');

            // PUTS (and replaces!) the currentBundle of vouchers!
            Route::put('/registrations/{registration}/vouchers', [
                'as' => 'store.registration.vouchers.put',
                'uses' => 'BundleController@update',
            ])->where('registration', '^[0-9]+$');

            // View a registrations vouchers
            Route::get('/registrations/{registration}/voucher-manager', [
                'as' => 'store.registration.voucher-manager',
                'uses' => 'BundleController@create'
            ])->where('registration', '^[0-9]+$');

            // Fetches a registration's collection history
            Route::get(
                '/registrations/{registration}/collection-history',
                'HistoryController@show'
            )->name('store.registration.collection-history')->where('registration', '^[0-9]+$');

            // Removes a voucher from the current bundle
            Route::delete(
                '/registrations/{registration}/vouchers/{voucher}',
                'BundleController@removeVoucherFromCurrentBundle'
            )->name('store.registration.voucher.delete'
            )->where('registration', '^[0-9]+$'
            )->where('voucher', '^[0-9]+$');

            // Removes all the vouchers in the current bundle
            Route::delete(
                '/registrations/{registration}/vouchers',
                'BundleController@removeAllVouchersFromCurrentBundle'
            )->name('store.registration.vouchers.delete')->where('registration', '^[0-9]+$');

            // Add vouchers to bundle
            Route::post(
                '/registrations/{registration}/vouchers',
                'BundleController@addVouchersToCurrentBundle'
            )->name('store.registration.vouchers.post')->where('registration', '^[0-9]+$');
        }
    );

    Route::group(
        ['middleware' => 'can:export,App\CentreUser'],
        function () {

            // ALL centres registrations as a summary spreadsheet
            Route::get('/centres/registrations/summary', [
                'as' => 'store.centres.registrations.summary',
                'uses' => 'CentreController@exportRegistrationsSummary',
            ]);

            // ALL vouchers and extra details, in a format suitable for the MVL sheets.
            // As this includes participant ID access, it has "can:export registrations".
            Route::get('/vouchers/master-log', [
                'as' => 'store.vouchers.mvl.export',
                'uses' => 'VoucherController@exportMasterVoucherLog',
            ]);
        }
    );

    Route::group(
        ['middleware' => 'can:viewRelevantCentre,centre'],
        function () {

            // Print a Specific Centre's Registrations list
            // Anyone who can view a centre can do this
            Route::get('/centres/{centre}/registrations/collection', [
                'as' => 'store.centre.registrations.collection',
                'uses' => 'CentreController@printCentreCollectionForm',
            ])->where('centre', '^[0-9]+$');

            // Export A specific centres' registrations summary spreadsheet.
            // anyone who can view a centre AND download can do this.
            Route::get('/centres/{centre}/registrations/summary', [
                'as' => 'store.centre.registrations.summary',
                'uses' => 'CentreController@exportRegistrationsSummary',
            ])->middleware(['can:download,App\CentreUser'])->where('centre', '^[0-9]+$');
        }
    );
});
