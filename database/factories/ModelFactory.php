<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Sponsor::class, function (Faker\Generator $faker) {

    return [
        'name' => $faker->company,
        'shortcode' => 'UNK',
    ];
});

$factory->define(App\Market::class, function (Faker\Generator $faker) {

    if ($sponsor_ids = \DB::table('sponsors')->select('id')->get()->toArray()) {
        $sponsor_id = $faker->randomElement($sponsor_ids)->id;
    } else {
        $sponsor = factory(App\Sponsor::class)->create(['name' => 'Null Adminstrations']);
        $sponsor_id = $sponsor->id;
    }

    return [
        'name' => $faker->company,
        'location' => $faker->postcode,
        'sponsor_id' => $sponsor_id // a random sponsor
    ];
});

$factory->define(App\Trader::class, function (Faker\Generator $faker) {

    return [
        'name' => $faker->name,
        'picURL' => null,
        'market_id' => null,
    ];
});

$factory->define(App\Voucher::class, function (Faker\Generator $faker) {

    if ($sponsor_ids = \DB::table('sponsors')->select('id')->get()->toArray()) {
        $sponsor_id = $faker->randomElement($sponsor_ids)->id;
    } else {
        // there are no sponsors. odd. make a null one
        $sponsor = factory(App\Sponsor::class)->create(['name' => 'Null Sponsors Inc.']);
        $sponsor_id = $sponsor->id;
    }

    return [
        'sponsor_id' => $sponsor_id, // a random sponsor
        'code' => $faker->ean8, // 8 digit barcode
        'currentstate' => 'requested'
    ];
});

