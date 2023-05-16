<?php

use Carbon\Carbon;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
 */

// route names have data.x ; append Data/ to find the controllers ; admin guards
Route::name('data.')
    ->namespace('Data')
    ->middleware('auth:admin')
    ->group(function () {
        // For now these routes are only available in dev and staging environs.
        Route::resource('vouchers', 'VoucherController', [
            'only' => ['index', 'show',],
        ]);

        Route::resource('users', 'UserController', [
            'only' => ['index',],
        ]);

        Route::resource('markets', 'MarketController', [
            'only' => ['index',],
        ]);

        Route::resource('traders', 'TraderController', [
            'only' => ['index',],
        ]);

        // Temp route for demo only.
        Route::name('reset')
            ->get('reset', function () {
                $process = new Process(['php', '../artisan', 'migrate:refresh', '--seed', '--force']);
                $process->run();
                $process = new Process(['php', '../artisan', 'passport:install']);
                $process->run();

                $new_secret = DB::table('oauth_clients')->where('id', 2)->pluck('secret')[0];
                $env_file_path = base_path('.env');
                $old_secret = env('PASSWORD_CLIENT_SECRET');
                file_put_contents($env_file_path, preg_replace(
                    "/^PASSWORD_CLIENT_SECRET={$old_secret}/m",
                    "PASSWORD_CLIENT_SECRET={$new_secret}",
                    file_get_contents($env_file_path)
                ));

                return Redirect::route('admin.dashboard')
                    ->with('message', 'Reseeded @' . Carbon::now());
            });
    });
