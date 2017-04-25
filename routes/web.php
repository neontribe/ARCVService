<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('voucher/{code}/{transition}', function (App\Voucher $voucher, $transition)
{
    // make sure you have autheticated user by route middleware or Auth check

    try {
        $voucher->state($transition);
    } catch (Exception $e) {
        abort(500, $e->getMessage());
    }
    return $voucher->history()->get();
});
