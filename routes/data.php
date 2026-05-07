<?php

use App\Http\Controllers\Service\Data\FamilyContactsController;
use App\Http\Controllers\Service\Data\MarketController;
use App\Http\Controllers\Service\Data\TraderController;
use App\Http\Controllers\Service\Data\UserController;
use App\Http\Controllers\Service\Data\VoucherController;
use App\Http\Middleware\IsNotProduction;
use App\Jobs\ResetDemoEnvironment;
use Illuminate\Support\Facades\Redirect;

/*
|--------------------------------------------------------------------------
| Data Routes
|--------------------------------------------------------------------------
*/

// Note: ->namespace() has been removed in Laravel 12. Controllers are now
// referenced directly via fully-qualified class imports above.

Route::name('data.')
    ->middleware('auth:admin')
    ->group(static function () {

        // For now these routes are only available in dev and staging environments.
        Route::resource('vouchers', VoucherController::class)->only(['index', 'show']);
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('markets', [MarketController::class, 'index'])->name('markets.index');
        Route::get('traders', [TraderController::class, 'index'])->name('traders.index');
        // Invokable
        Route::get('families/contacts', FamilyContactsController::class)
            ->withoutMiddleware(IsNotProduction::class)
            ->name('families.contacts.download');

        // Temporary route for demo only.
        Route::get('reset', static function () {
            if (Gate::allows('take-developer-actions')) {
                ResetDemoEnvironment::dispatch();

                return Redirect::route('admin.dashboard')
                    ->with('message', 'Reset queued');
            }
            return Redirect::route('admin.dashboard')
                ->with('error', 'Action Denied');
        })->name('reset');
    });
