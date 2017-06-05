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
    $process = new Process('php artisan migrate:refresh --seed --force');
    $process->run();
    return Redirect::route('dashboard')
        ->with('message', 'Reseeded @' . Carbon::now());
});
