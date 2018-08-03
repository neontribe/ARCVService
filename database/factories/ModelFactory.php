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

/**
 *  Users for Market and CC
 */

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

$factory->define(App\CentreUser::class, function (Faker\Generator $faker) {
    static $password;

    $roles = ['centre_user', 'foodmatters_user'];

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'role' => $roles[mt_rand(0, count($roles) - 1)],
    ];
});

/**
 * A model with a random Centre
 * Should be Registration or CentreUser
 */
$factory->state(App\CentreUser::class, 'withRandomCentre', function () {

    $centres  = App\Centre::get();

    if ($centres->count() > 0) {
        // Pick a random Centre
        $centre = $centres[random_int(0, $centres->count()-1)];
    } else {
        // There should be at least one Centre
        $centre = factory(App\Centre::class)->create();
    }

    return [
        'centre_id' => $centre->id,
    ];
});

/**
 * Sponsor for testing
 */
$factory->define(App\Sponsor::class, function (Faker\Generator $faker) {

    $counties = [
        "Barnfordshire",
        "Barsetshire",
        "Borsetshire",
        "Burtondon",
        "Diddlesex",
        "Downshire",
        "Ffhagdiwedd",
        "Gaultshire",
        "Glebeshire",
        "Glenshire",
        "West PassingBury",
        "Loamshire",
        "Mangelwurzelshire",
        "Markshire",
        "Mallardshire",
        "Melfordshire",
        "Mertonshire",
        "Mortshire",
        "Midsomer",
        "Mummerset",
        "Naptonshire",
        "Oatshire",
        "Placefordshire",
        "Quantumshire",
        "Radfordshire",
        "Redshire",
        "Russetshire",
        "Rutshire",
        "Shiring",
        "Shroudshire",
        "Slopshire",
        "Southmoltonshire",
        "South Riding",
        "Stonyshire",
        "Trumptonshire",
        "Wessex",
        "Westershire",
        "Waringham",
        "Westshire",
        "Winshire",
        "Wordenshire",
        "Worfordshire",
        "South Worfordshire",
        "Wyverndon",
    ];

    $index = $faker->unique()->numberBetween(0, 43);

    return [
        'name' => $counties[$index],
        'shortcode' => "RV" . str_pad($index, 3, "0", STR_PAD_LEFT),
    ];
});

/**
 *  Models for Market Testing
 */

$factory->define(App\Market::class, function (Faker\Generator $faker) {
    if ($sponsor_ids = App\Sponsor::pluck('id')->toArray()) {
        $sponsor_id = $faker->randomElement($sponsor_ids);
    } else {
        $sponsor = factory(App\Sponsor::class)->create(['name' => 'Null Adminstrations']);
        $sponsor_id = $sponsor->id;
    }

    $payment_sentence = $faker->sentence(12, true);

    return [
        'name' => $faker->company,
        'location' => $faker->postcode,
        'sponsor_id' => $sponsor_id, // a random sponsor
        'payment_message' => $payment_sentence,
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


/**
 *  Models for CC Testing
 */

// Centre, with random sponsor
$factory->define(App\Centre::class, function (Faker\Generator $faker) {

    $sponsors = App\Sponsor::get();

    if ($sponsors->count() > 0) {
        // Pick a random Sponsor
        $sponsor = $sponsors[random_int(0, $sponsors->count()-1)];
    } else {
        // There must be at least one Sponsor
        $sponsor = factory(App\Sponsor::class)->create();
    }

    $name = $faker->streetName;

    return [
        'name' => $name,
        // *Probably* not going to generate a duplicate...
        // But metaphone will occassionally return 6 chars if endish char is an X -> KS
        // https://bugs.php.net/bug.php?id=60123
        // Also might return 4 chars - but that's ok for seeds? Or do we pad?
        'prefix' => substr(metaphone($name, 5), 0, 5),
        'sponsor_id' => $sponsor->id,
        // print_pref will be 'collection' by default.
        // To ensure we always have one 'individual', adding to seeder as well.
        'print_pref' => $faker->randomElement(['individual', 'collection']),
    ];
});

// Registration
$factory->define(App\Registration::class, function () {

    $eligibilities = ['healthy-start', 'other'];

    $centre = App\Centre::inRandomOrder()->first();
    if (is_null($centre)) {
        $centre = factory(App\Centre::class)->create();
    }
    $family = factory(App\Family::class)->make();

    // Set initial centre (and thus, rvid)
    $family->lockToCentre($centre);
    $family->save();

    // Add dependent models
    $family->carers()->saveMany(factory(App\Carer::class, random_int(1, 3))->make());
    $family->children()->saveMany(factory(\App\Child::class, random_int(0, 4))->make());

    return [
        'centre_id' => $centre->id,
        'family_id' => $family->id,
        'eligibility' => $eligibilities[mt_rand(0, count($eligibilities) - 1)],
        'consented_on' => Carbon\Carbon::now(),
    ];
});

// Family
$factory->define(App\Family::class, function () {
    // One day there will be useful things here.
    return [];
});

// Carer
$factory->define(App\Carer::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->firstName ." ". $faker->lastName,
    ];
});

// Random Age Child
$factory->define(App\Child::class, function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::createFromTimestamp($faker->dateTimeBetween('-6 years', '+9 months')->getTimestamp());
    $dob = $dob->startOfMonth();
    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - unborn
$factory->defineAs(App\Child::class, 'unbornChild', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::createFromTimestamp($faker->dateTimeBetween('+2 month', '+8 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - under 1
$factory->defineAs(App\Child::class, 'underOne', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::createFromTimestamp($faker->dateTimeBetween('-10 months', '-2 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - almost born
$factory->defineAs(App\Child::class, 'almostBorn', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::now()->startOfMonth()->addMonths(1);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});


// Child - almost 1
$factory->defineAs(App\Child::class, 'almostOne', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::now()->startOfMonth()->subMonths(11);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - readyForSchool when the school_month rolls around
$factory->defineAs(App\Child::class, 'readyForSchool', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $dob = Carbon\Carbon::now()->startOfMonth()->subYears(4);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - under School Age
$factory->defineAs(App\Child::class, 'underSchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::createFromTimestamp($faker->dateTimeBetween('-32 months', '-14 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - over SchoolAge
$factory->defineAs(App\Child::class, 'overSchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon\Carbon::createFromTimestamp($faker->dateTimeBetween('-10 years', '-6 years')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});
//Note
$factory->define(App\Note::class, function (Faker\Generator $faker){

    return [
        'content' => 'this is some note content',
        'family_id' => 1,
        'user_id' => 1,
    ];
});