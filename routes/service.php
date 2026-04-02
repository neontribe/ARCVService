<?php

use App\Http\Controllers\Service\Admin\CentresController;
use App\Http\Controllers\Service\Admin\CentreUsersController;
use App\Http\Controllers\Service\Admin\DeliveriesController;
use App\Http\Controllers\Service\Admin\MarketsController;
use App\Http\Controllers\Service\Admin\PaymentsController;
use App\Http\Controllers\Service\Admin\SponsorsController;
use App\Http\Controllers\Service\Admin\TradersController;
use App\Http\Controllers\Service\Admin\VouchersController;
use App\Http\Controllers\Service\AdminController;
use App\Http\Controllers\Service\Auth\ForgotPasswordController;
use App\Http\Controllers\Service\Auth\LoginController;
use App\Http\Controllers\Service\Auth\ResetPasswordController;
use App\Http\Controllers\Service\VersionController;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

// Admin (Service) Authentication Routes...

Route::get('login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('login', [LoginController::class, 'login']);

Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');

Route::get('version', [VersionController::class, 'version'])->name('version');

// Password Reset
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('admin.password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('admin.password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('admin.password.reset')
    ->where('token', '[0-9a-f]{64}');
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

//
Route::group(['middleware' => 'auth:admin'], static function () {

    // Must be logged in to log out.
    Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');

    // Voucher Management
    Route::get('vouchers', [VouchersController::class, 'index'])->name('admin.vouchers.index');
    // ...create form
    Route::get('vouchers/create', [VouchersController::class, 'create'])->name('admin.vouchers.create');
    // ...void form
    Route::get('vouchers/void', [VouchersController::class, 'void'])->name('admin.vouchers.void');

    Route::get('vouchers/search', [VouchersController::class, 'search'])->name('admin.vouchers.search');
    // ...store batch of printed
    Route::post('vouchers', [VouchersController::class, 'storeBatch'])->name('admin.vouchers.storebatch');
    // ...patch because changing state of a partial collection of vouchers.
    Route::patch('vouchers', [VouchersController::class, 'retireBatch'])->name('admin.vouchers.retirebatch');
    Route::get('vouchers/{voucher}', [VouchersController::class, 'viewOne'])
        ->name('service.vouchers.viewone')
        ->whereNumber('voucher');

    //Payment Management
    Route::get('payments', [PaymentsController::class, 'index'])->name('admin.payments.index');
    Route::get('payments/payment-request/{paymentUuid}', [PaymentsController::class, 'show'])
        ->name('admin.payment-request.show');
    Route::put('payments/payment-request/{paymentUuid}', [PaymentsController::class, 'update'])
        ->name('admin.payment-request.update');
    Route::get('payments/trader-payment-history/{trader}', [TradersController::class, 'traderHistory'])
        ->name('admin.trader-payment-history.show')
        ->whereNumber('trader');

    // Worker Management
    Route::get('workers', [CentreUsersController::class, 'index'])->name('admin.centreusers.index');
    Route::get('workers/create', [CentreUsersController::class, 'create'])->name('admin.centreusers.create');
    Route::get('workers/download', [CentreUsersController::class, 'download'])->name('admin.centreusers.download');
    Route::post('workers', [CentreUsersController::class, 'store'])->name('admin.centreusers.store');
    Route::put('workers/{id}', [CentreUsersController::class, 'update'])
        ->name('admin.centreusers.update')
        ->whereNumber('id');
    Route::get('workers/{id}/edit', [CentreUsersController::class, 'edit'])
        ->name('admin.centreusers.edit')
        ->whereNumber('id');
    Route::get('workers/{id}/toggle', [CentreUsersController::class, 'toggle'])
        ->name('admin.centreusers.toggle')
        ->whereNumber('id');
    Route::get('workers/{id}/delete', [CentreUsersController::class, 'delete'])
        ->name('admin.centreusers.delete')
        ->whereNumber('id');

    // Centre Management
    Route::get('centres', [CentresController::class, 'index'])->name('admin.centres.index');
    Route::get('centres/create', [CentresController::class, 'create'])->name('admin.centres.create');
    Route::post('centres', [CentresController::class, 'store'])->name('admin.centres.store');
    Route::get('centres/{id}/neighbours', [CentresController::class, 'getNeighboursAsJson'])
        ->name('admin.centre_neighbours.index')
        ->whereNumber('id');
    Route::put('centres/{id}/update', [CentresController::class, 'update'])
        ->name('admin.centres.update')
        ->whereNumber('id');
    Route::get('centres/{id}/edit', [CentresController::class, 'edit'])
        ->name('admin.centres.edit')
        ->whereNumber('id');

    // Sponsor Management
    Route::get('sponsors', [SponsorsController::class, 'index'])->name('admin.sponsors.index');
    Route::get('sponsors/create', [SponsorsController::class, 'create'])->name('admin.sponsors.create');
    Route::post('sponsors', [SponsorsController::class, 'store'])->name('admin.sponsors.store');
    Route::get('sponsors/{id}', [SponsorsController::class, 'edit'])
        ->name('admin.sponsors.edit')
        ->whereNumber('id');
    Route::put('sponsors/{id}', [SponsorsController::class, 'update'])
        ->name('admin.sponsors.update')
        ->whereNumber('id');

    // Deliveries Management
    Route::get('deliveries', [DeliveriesController::class, 'index'])->name('admin.deliveries.index');
    Route::get('deliveries/create', [DeliveriesController::class, 'create'])->name('admin.deliveries.create');
    Route::post('deliveries/store', [DeliveriesController::class, 'store'])->name('admin.deliveries.store');

    // Market Management
    Route::get('markets', [MarketsController::class, 'index'])->name('admin.markets.index');
    Route::get('markets/create', [MarketsController::class, 'create'])->name('admin.markets.create');
    Route::post('markets', [MarketsController::class, 'store'])->name('admin.markets.store');
    Route::get('markets/{id}/edit', [MarketsController::class, 'edit'])
        ->name('admin.markets.edit')
        ->whereNumber('id');
    Route::put('markets/{id}', [MarketsController::class, 'update'])
        ->name('admin.markets.update')
        ->whereNumber('id');

    // Trader Management
    Route::get('traders', [TradersController::class, 'index'])->name('admin.traders.index');
    Route::get('traders/create', [TradersController::class, 'create'])->name('admin.traders.create');
    Route::get('traders/download', [TradersController::class, 'download'])->name('admin.traders.download');
    Route::post('traders', [TradersController::class, 'store'])->name('admin.traders.store');
    Route::get('traders/{id}/edit', [TradersController::class, 'edit'])
        ->name('admin.traders.edit')
        ->whereNumber('id');
    Route::put('traders/{id}', [TradersController::class, 'update'])
        ->name('admin.traders.update')
        ->whereNumber('id');
});
