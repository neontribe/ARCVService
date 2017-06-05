<?php

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

// TODO: sort these out into proper PS-4 heirarchy or something...

Route::resource('vouchers', 'VoucherController', [
    'only' => ['index','show','store']
]);

Route::resource('users', 'UserController', [
    'only' => ['index','show',]
]);

Route::resource('markets', 'MarketController', [
    'only' => ['index','show',]
]);

Route::resource('traders', 'TraderController', [
    'only' => ['index','show']
]);


// Temp route for demo only.
Route::get('reset-data', function() {
    $process = new Process('
        php ../artisan migrate:refresh --seed --force
    ');
    $process->run();
    $process = new Process('
        php ../artisan passport:install
    ');
    $process->run();

    $new_secret = DB::table('oauth_clients')->where('id', 2)->pluck('secret')[0];
    $env_file_path = base_path('.env');
    $old_secret = env('PASSWORD_CLIENT_SECRET');
    file_put_contents($env_file_path, preg_replace(
        "/^PASSWORD_CLIENT_SECRET={$old_secret}/m",
        "PASSWORD_CLIENT_SECRET={$new_secret}",
        file_get_contents($env_file_path)
    ));

    return Redirect::route('dashboard')
        ->with('message', 'Reseeded @' . Carbon::now());
});
