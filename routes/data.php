<?php

use App\Http\Controllers\Service\Data\FamilyContactsController;
use App\Http\Controllers\Service\Data\MarketController;
use App\Http\Controllers\Service\Data\TraderController;
use App\Http\Controllers\Service\Data\UserController;
use App\Http\Controllers\Service\Data\VoucherController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Process\Process;

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
        Route::get('families/contacts', FamilyContactsController::class)->name('families.contacts.download');

        // Temporary route for demo only.
        Route::get('reset', static function () {
            if (Gate::allows('take-developer-actions')) {
                $process = new Process(['php', '../artisan', 'migrate:refresh', '--seed', '--force']);
                $process->run();

                $process = new Process([
                    'php',
                    '../artisan',
                    'passport:client',
                    '--password',
                    '--name="Rose Vouchers Password Grant Client"',
                    '--provider=users',
                ]);
                $process->run();

                $newSecret = DB::table('oauth_clients')->where('id', 1)->pluck('secret')[0];
                $envFilePath = base_path('.env');
                $oldSecret = env('PASSWORD_CLIENT_SECRET');

                file_put_contents($envFilePath, preg_replace(
                    "/^PASSWORD_CLIENT_SECRET={$oldSecret}/m",
                    "PASSWORD_CLIENT_SECRET={$newSecret}",
                    file_get_contents($envFilePath)
                ));

                return Redirect::route('admin.dashboard')
                    ->with('message', 'Reseeded @' . Carbon::now());
            }
            return Redirect::route('admin.dashboard')
                ->with('error', 'Action Denied');
        })->name('reset');
    });
