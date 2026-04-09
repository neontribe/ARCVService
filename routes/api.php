<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\ForgotPasswordController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\QueueController;
use App\Http\Controllers\API\TraderController;
use App\Http\Controllers\API\VoucherController;
use App\Http\Controllers\API\TransitionController;
use App\Http\Controllers\API\LoggingController;

/*
|--------------------------------------------------------------------------
| API Routes for ARCV Market App
|--------------------------------------------------------------------------
*/

Route::post('login', [LoginController::class, 'login'])->name('api.login');
Route::post('login/refresh', [LoginController::class, 'refresh'])->name('api.login.refresh');
Route::post('user/lost_password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('api.user.lost_password');
Route::post('user/lost_password/reset', [ResetPasswordController::class, 'reset'])->name('api.user.reset_password');

// Authenticated routes
Route::middleware('auth:api')->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->name('api.logout');

    Route::get('queue/{jobStatus}', [QueueController::class, 'show'])->name('api.queued-task.show');

    Route::get('traders', [TraderController::class, 'index'])->name('api.traders');

    Route::middleware('can:view,trader')->group(function () {
        Route::get('traders/{trader}', [TraderController::class, 'show'])
            ->name('api.trader')
            ->whereNumber('trader');

        Route::get('traders/{trader}/vouchers', [TraderController::class, 'showVouchers'])
            ->name('api.trader.vouchers')
            ->middleware(['setEtag', 'ifNoneMatch'])
            ->whereNumber('trader');

        Route::get('traders/{trader}/voucher-history', [TraderController::class, 'showVoucherHistory'])
            ->name('api.trader.voucher-history')
            ->middleware(['setEtag', 'ifNoneMatch'])
            ->whereNumber('trader');

        Route::post('traders/{trader}/voucher-history-email', [TraderController::class, 'emailVoucherHistory'])
            ->name('api.trader.voucher-history-email')
            ->middleware(['setEtag', 'ifNoneMatch'])
            ->whereNumber('trader');
    });

    // Legacy transition for old clients
    Route::post('vouchers', [VoucherController::class, 'legacyTransition'])
        ->name('api.voucher.transition')
        ->middleware('can:collect,App\Voucher');

    // New voucher transition routes
    Route::post('vouchers/transitions', [TransitionController::class, 'store'])
        ->name('api.vouchers.transition-responses.store')
        ->middleware('can:collect,App\Voucher');

    Route::get('vouchers/transitions/{jobStatus}', [TransitionController::class, 'show'])
        ->name('api.vouchers.transition-response.show')
        ->whereNumber('jobStatus');

    Route::put('log', [LoggingController::class, 'log'])->name('api.log');
});
