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

$factory->define(App\AdminUser::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

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
        'shortcode' => $faker->regexify('[A-Z]{2,5}'),
    ];
});

$factory->define(App\Market::class, function (Faker\Generator $faker) {
    if ($sponsor_ids = App\Sponsor::pluck('id')->toArray()) {
        $sponsor_id = $faker->randomElement($sponsor_ids);
    } else {
        $sponsor = factory(App\Sponsor::class)->create(['name' => 'Null Adminstrations']);
        $sponsor_id = $sponsor->id;
    }

    return [
        'name' => $faker->company,
        'location' => $faker->postcode,
        'sponsor_id' => $sponsor_id, // a random sponsor
    ];
});

$factory->define(App\Trader::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'pic_url' => null,
        'market_id' => null,
    ];
});

/**
 * Trader with nullables filled.
 */
$factory->defineAs(App\Trader::class, 'withnullable', function ($faker) use ($factory) {
    $trader = $factory->raw(App\Trader::class);

    return array_merge($trader, [
        'pic_url' => 'https://placeholdit.com/150x150',
        'market_id' => factory(App\Market::class)->create()->id,
    ]);
});

/**
 * Voucher with a random current state.
 */
$factory->define(App\Voucher::class, function (Faker\Generator $faker) {
    if ($sponsor_ids = App\Sponsor::pluck('id')->toArray()) {
        $sponsor_id = $faker->randomElement($sponsor_ids);
    } else {
        // there are no sponsors. odd. make a null one
        $sponsor = factory(App\Sponsor::class)->create([
            'name' => 'Null Sponsors Inc.'
        ]);
        $sponsor_id = $sponsor->id;
    }
    $states = config('state-machine.Voucher.states');
    $currentstate = $faker->randomElement($states);

    // Todo Create the voucher_states that got us here.
    $transitions = config('state-machine.Voucher.transitions');
    // Todo find $currentstate in the $transitions[$key]['from']

    $shortcode = App\Sponsor::find($sponsor_id)->shortcode;
    return [
        // A random sponsor
        'sponsor_id' => $sponsor_id,
        // Sponsor code + 4-8 integers
        'code' => $shortcode . $faker->regexify('[0-9]{4,8}'),
        'currentstate' => $currentstate,
    ];
});

/**
 * Voucher with currentstate requested.
 */
$factory->defineAs(App\Voucher::class, 'requested', function ($faker) use ($factory) {
    $voucher = $factory->raw(App\Voucher::class);

    return array_merge($voucher, [
        'currentstate' => 'requested',
    ]);
});

/**
 * Voucher with currentstate allocated.
 */
$factory->defineAs(App\Voucher::class, 'allocated', function ($faker) use ($factory) {
    $voucher = $factory->raw(App\Voucher::class);

    // Todo create the voucher_states on the road to allocated.
    factory(App\VoucherState::class)->create();
    factory(App\VoucherState::class)->create([
        'transition' => 'print',
        'from' => 'ordered',
        'to' => 'printed',
    ]);
    factory(App\VoucherState::class)->create([
        'transition' => 'dispatch',
        'from' => 'printed',
        'to' => 'dispatched',
    ]);
    factory(App\VoucherState::class)->create([
        'transition' => 'allocate',
        'from' => 'printed',
        'to' => 'allocated',
    ]);
    return array_merge($voucher, [
        'currentstate' => 'allocated',
    ]);
});

$factory->define(App\VoucherState::class, function (Faker\Generator $faker) {

    // Factory adds initial values - overwrite when we create.
    // There will be a batter way to seed states - but for now
    // just need a way to force one.
    return [
        'transition' => 'order',
        'from' => 'requested',
        'user_id' => 1,
        'voucher_id' => 1,
        'to' => 'ordered',
        // Not sure what source refers to.
        'source' => 'factory',
    ];
});
