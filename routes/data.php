<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Process\Process;
use App\Http\Controllers\Service\Data\VoucherController;
use App\Http\Controllers\Service\Data\UserController;
use App\Http\Controllers\Service\Data\MarketController;
use App\Http\Controllers\Service\Data\TraderController;

/*
|--------------------------------------------------------------------------
| Data Routes
|--------------------------------------------------------------------------
*/

// Note: ->namespace() has been removed in Laravel 12. Controllers are now
// referenced directly via fully-qualified class imports above.

Route::name('data.')
    ->middleware('auth:admin')
    ->group(function () {

        // For now these routes are only available in dev and staging environments.
        Route::resource('vouchers', VoucherController::class)->only(['index', 'show']);
        Route::resource('users', UserController::class)->only(['index']);
        Route::resource('markets', MarketController::class)->only(['index']);
        Route::resource('traders', TraderController::class)->only(['index']);

        // Temporary route for demo only.
        Route::get('reset', static function () {
            $process = new Process(['php', '../artisan', 'migrate:refresh', '--seed', '--force']);
            $process->run();

            $process = new Process(['php', '../artisan', 'passport:install']);
            $process->run();

            $newSecret = DB::table('oauth_clients')->where('id', 2)->pluck('secret')[0];
            $envFilePath = base_path('.env');
            $oldSecret = env('PASSWORD_CLIENT_SECRET');

            file_put_contents($envFilePath, preg_replace(
                "/^PASSWORD_CLIENT_SECRET={$oldSecret}/m",
                "PASSWORD_CLIENT_SECRET={$newSecret}",
                file_get_contents($envFilePath)
            ));

            return Redirect::route('admin.dashboard')
                ->with('message', 'Reseeded @' . Carbon::now());
        })->name('reset');
    });
