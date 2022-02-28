<?php

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

// Admin (Service) Authentication Routes...
Route::get('login', [
    'as' => 'admin.login',
    'uses' => 'Auth\LoginController@showLoginForm',
]);
Route::post('login', 'Auth\LoginController@login');

Route::get('/', 'AdminController@index')->name('admin.dashboard');

// Admin (Service) Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')
    ->name('admin.password.request')
;
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')
    ->name('admin.password.email')
;
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')
    ->name('admin.password.reset')
    ->where('token', '[0-9a-f]{80}');
;
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::group(['middleware' => 'auth:admin'], function () {
    // Voucher Management
    Route::get('vouchers', [
        'as' => 'admin.vouchers.index',
        'uses' => 'Admin\VouchersController@index',
    ]);
    // ...create form
    Route::get('vouchers/create', [
        'as' => 'admin.vouchers.create',
        'uses' => 'Admin\VouchersController@create',
    ]);
    // ...void form
    Route::get('vouchers/void', [
        'as' => 'admin.vouchers.void',
        'uses' => 'Admin\VouchersController@void',
    ]);
    // ...store batch of printed
    Route::post('vouchers', [
        'as' => 'admin.vouchers.storebatch',
        'uses' => 'Admin\VouchersController@storeBatch',
    ]);
    // ...patch because changing state of a partial collection of vouchers.
    Route::patch('vouchers', [
        'as' => 'admin.vouchers.retirebatch',
        'uses' => 'Admin\VouchersController@retireBatch',
    ]);
    Route::get('vouchers/{voucher}', [
        'as' => 'service.vouchers.viewone',
        'uses' => 'Admin\VouchersController@viewOne',
    ])->where('voucher', '^[0-9]+$');

    Route::get('vouchers/search', [
        'as' => 'admin.vouchers.search',
        'uses' => 'Admin\VouchersController@search',
    ]);

    // Worker Management
    Route::get('workers', [
        'as' => 'admin.centreusers.index',
        'uses' => 'Admin\CentreUsersController@index',
    ]);
    Route::get('workers/create', [
        'as' => 'admin.centreusers.create',
        'uses' => 'Admin\CentreUsersController@create',
    ]);
    Route::post('workers', [
        'as' => 'admin.centreusers.store',
        'uses' => 'Admin\CentreUsersController@store',
    ]);
    Route::put('workers/{id}', [
        'as' => 'admin.centreusers.update',
        'uses' => 'Admin\CentreUsersController@update',
    ])->where('id', '^[0-9]+$');
    Route::get('workers/{id}/edit', [
        'as' => 'admin.centreusers.edit',
        'uses' => 'Admin\CentreUsersController@edit',
    ])->where('id', '^[0-9]+$');
    Route::get('workers/download', [
        'as' => 'admin.centreusers.download',
        'uses' => 'Admin\CentreUsersController@download',
    ]);
    Route::get('workers/{id}/delete', [
        'as' => 'admin.centreusers.delete',
        'uses' => 'Admin\CentreUsersController@delete',
    ])->where('id', '^[0-9]+$');

    // Centre Management
    Route::get('centres', [
        'as' => 'admin.centres.index',
        'uses' => 'Admin\CentresController@index',
    ]);
    Route::get('centres/create', [
        'as' => 'admin.centres.create',
        'uses' => 'Admin\CentresController@create',
    ]);
    Route::get('centres/{id}/neighbours', [
        'as' => 'admin.centre_neighbours.index',
        'uses' => 'Admin\CentresController@getNeighboursAsJson'
    ])->where('id', '^[0-9]+$');
    Route::post('centres', [
        'as' => 'admin.centres.store',
        'uses' => 'Admin\CentresController@store',
    ]);

    // Sponsor Management
    Route::get('sponsors', [
        'as' => 'admin.sponsors.index',
        'uses' => 'Admin\SponsorsController@index',
    ]);
    Route::get('sponsors/create', [
        'as' => 'admin.sponsors.create',
        'uses' => 'Admin\SponsorsController@create',
    ]);
    Route::post('sponsors', [
        'as' => 'admin.sponsors.store',
        'uses' => 'Admin\SponsorsController@store',
    ]);

    Route::post('logout', [
        'as' => 'admin.logout',
        'uses' => 'Auth\LoginController@logout',
    ]);

    // Deliveries Management
    Route::get('deliveries', [
        'as' => 'admin.deliveries.index',
        'uses' => 'Admin\DeliveriesController@index',
    ]);
    Route::get('deliveries/create', [
        'as' => 'admin.deliveries.create',
        'uses' => 'Admin\DeliveriesController@create',
    ]);
    Route::post('deliveries/store', [
        'as' => 'admin.deliveries.store',
        'uses' => 'Admin\DeliveriesController@store',
    ]);

    // Market Management
    Route::get('markets', [
        'as' => 'admin.markets.index',
        'uses' => 'Admin\MarketsController@index',
    ]);
    Route::get('markets/create', [
        'as' => 'admin.markets.create',
        'uses' => 'Admin\MarketsController@create',
    ]);
    Route::post('markets', [
        'as' => 'admin.markets.store',
        'uses' => 'Admin\MarketsController@store',
    ]);
    Route::get('markets/{id}/edit', [
        'as' => 'admin.markets.edit',
        'uses' => 'Admin\MarketsController@edit',
    ])->where('id', '^[0-9]+$');
    Route::put('markets/{id}', [
        'as' => 'admin.markets.update',
        'uses' => 'Admin\MarketsController@update',
    ])->where('id', '^[0-9]+$');

    // Trader Management
    Route::get('traders', [
        'as' => 'admin.traders.index',
        'uses' => 'Admin\TradersController@index',
    ]);
    Route::get('traders/create', [
        'as' => 'admin.traders.create',
        'uses' => 'Admin\TradersController@create',
    ]);
    Route::post('traders', [
        'as' => 'admin.traders.store',
        'uses' => 'Admin\TradersController@store',
    ]);
    Route::get('traders/{id}/edit', [
        'as' => 'admin.traders.edit',
        'uses' => 'Admin\TradersController@edit',
    ])->where('id', '^[0-9]+$');
    Route::put('traders/{id}', [
        'as' => 'admin.traders.update',
        'uses' => 'Admin\TradersController@update',
    ])->where('id', '^[0-9]+$');
    Route::get('traders/download', [
        'as' => 'admin.traders.download',
        'uses' => 'Admin\TradersController@download',
    ]);
});
