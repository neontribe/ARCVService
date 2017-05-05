<?php

use Illuminate\Http\Request;

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
