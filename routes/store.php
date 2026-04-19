<?php

use App\Http\Controllers\Store\Auth\LoginController;
use App\Http\Controllers\Store\Auth\ForgotPasswordController;
use App\Http\Controllers\Store\Auth\ResetPasswordController;
use App\Http\Controllers\Store\DashboardController;
use App\Http\Controllers\Store\SessionController;
use App\Http\Controllers\Store\RegistrationController;
use App\Http\Controllers\Store\FamilyController;
use App\Http\Controllers\Store\BundleController;
use App\Http\Controllers\Store\HistoryController;
use App\Http\Controllers\Store\CentreController;
use App\Http\Controllers\Store\VoucherController;

/*
|--------------------------------------------------------------------------
| Store Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::get('login', [LoginController::class, 'showLoginForm'])->name('store.login');
Route::post('login', [LoginController::class, 'login']);

// Password Reset
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('store.password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('store.password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('store.password.reset')
    ->where('token', '[0-9a-f]{64}');
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

// Base redirect
Route::get('/', static function () {
    return redirect()->route('store.login');
})->name('store.base');

// Authenticated routes
Route::middleware('auth:store')->group(function (): void {

    Route::post('logout', [LoginController::class, 'logout'])
        ->name('store.logout');

    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('store.dashboard');

    Route::put('/session', [SessionController::class, 'update'])
        ->name('store.session.put');

    // Registrations
    Route::get('/registrations', [RegistrationController::class, 'index'])
        ->name('store.registration.index');
    Route::post('/registrations', [RegistrationController::class, 'store'])
        ->name('store.registration.store');
    Route::get('/registrations/create', [RegistrationController::class, 'create'])
        ->name('store.registration.create');
    Route::get('/registrations/print', [RegistrationController::class, 'printBatchIndividualFamilyForms'])
        ->name('store.registrations.print');

    // Specific registration actions (requires readOrUpdate policy)
    Route::middleware('can:readOrUpdate,registration')->group(function () {

        Route::get('/registrations/{registration}/edit', [RegistrationController::class, 'edit'])
            ->name('store.registration.edit')
            ->whereNumber('registration');

        Route::put('/registrations/{registration}', [RegistrationController::class, 'update'])
            ->name('store.registration.update')
            ->whereNumber('registration');

        Route::get('/registrations/{registration}/view', [RegistrationController::class, 'view'])
            ->name('store.registration.view')
            ->whereNumber('registration');

        Route::put('/registrations/{registration}/family', [FamilyController::class, 'update'])
            ->name('store.registration.family')
            ->whereNumber('registration');

        Route::put('/registrations/{registration}/rejoin', [FamilyController::class, 'rejoin'])
            ->name('store.registration.rejoin')
            ->whereNumber('registration');

        Route::get(
            '/registrations/{registration}/print',
            [RegistrationController::class, 'printOneIndividualFamilyForm']
        )
            ->name('store.registration.print')
            ->whereNumber('registration');

        Route::put('/registrations/{registration}/vouchers', [BundleController::class, 'update'])
            ->name('store.registration.vouchers.put')
            ->whereNumber('registration');

        Route::get('/registrations/{registration}/voucher-manager', [BundleController::class, 'create'])
            ->name('store.registration.voucher-manager')
            ->whereNumber('registration');

        Route::get('/registrations/{registration}/collection-history', [HistoryController::class, 'show'])
            ->name('store.registration.collection-history')
            ->whereNumber('registration');

        Route::delete(
            '/registrations/{registration}/vouchers/{voucher}',
            [BundleController::class, 'removeVoucherFromCurrentBundle']
        )
                ->name('store.registration.voucher.delete')
                ->whereNumber(['registration', 'voucher']);

        Route::delete(
            '/registrations/{registration}/vouchers',
            [BundleController::class, 'removeAllVouchersFromCurrentBundle']
        )
                ->name('store.registration.vouchers.delete')
                ->whereNumber('registration');

        Route::post('/registrations/{registration}/vouchers', [BundleController::class, 'addVouchersToCurrentBundle'])
            ->name('store.registration.vouchers.post')
            ->whereNumber('registration');

        Route::post(
            '/registrations/{registration}/vouchers/payment-requests',
            [BundleController::class, 'requestPayment']
        )
            ->name('store.registration.vouchers.payment-requests.post')
            ->whereNumber('registration');
    });

    // Export routes (requires export policy on CentreUser)
    Route::middleware('can:export,App\CentreUser')->group(function (): void {

        Route::get('/centres/registrations/summary', [CentreController::class, 'exportRegistrationsSummary'])
            ->name('store.centres.registrations.summary');

        Route::get('/vouchers/master-log', [VoucherController::class, 'exportMasterVoucherLog'])
            ->name('store.vouchers.mvl.export');

        Route::get('/vouchers/historical', [VoucherController::class, 'listVoucherLogs'])
            ->name('store.vouchers.mvl.historical');

        Route::get('/vouchers/download', [VoucherController::class, 'downloadAndDecryptVoucherLogs'])
            ->name('store.vouchers.mvl.download');
    });

    // Centre-scoped routes (requires viewRelevantCentre policy)
    Route::middleware('can:viewRelevantCentre,centre')->group(function (): void {

        Route::get('/centres/{centre}/registrations/collection', [CentreController::class, 'printCentreCollectionForm'])
            ->name('store.centre.registrations.collection')
            ->whereNumber('centre');

        Route::get('/centres/{centre}/registrations/summary', [CentreController::class, 'exportRegistrationsSummary'])
            ->name('store.centre.registrations.summary')
            ->middleware('can:download,App\CentreUser')
            ->whereNumber('centre');
    });
});
