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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */

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

/**
 * Standard CentreUser
 */
$factory->define(App\CentreUser::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'role' =>'centre_user',
    ];
});

/**
 * CentreUser who can Download.
 */
$factory->defineAs(App\CentreUser::class, 'withDownloader', function ($faker) use ($factory) {
    $cu = $factory->raw(App\CentreUser::class);

    return array_merge($cu, [
        'downloader' => true,
    ]);
});

/**
 * Specifically an Admin Centre User [foodmatters_user]
 */
$factory->defineAs(App\CentreUser::class, 'FMUser',function (Faker\Generator $faker) use ($factory){
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'role' => 'foodmatters_user',
        'downloader' => true
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
        'shortcode' => $faker->regexify('[A-Z]{4}'),
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
 * Empty bundle.
 */
$factory->define(App\Bundle::class, function (Faker\Generator $faker, $attributes) {

    // get/make  registration for a family
    $registration = isset($attributes['registration_id'])
        ? App\Registration::find($attributes['registration_id'])
        : factory(App\Registration::class)->create()
    ;

    // get/calculate and stash the entitlement
    $entitlement = isset($attributes['entitlement'])
        ? $attributes['entitlement']
        : $registration->getValuation()->getEntitlement()
    ;

    return [
        'registration_id' => $registration->id,
        'entitlement' => $entitlement
    ];
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
 * Voucher with currentstate printed.
 */
$factory->defineAs(App\Voucher::class, 'printed', function ($faker) use ($factory) {

    // As our starting state, we do not require a `voucher_state` to be generated.
    $voucher = $factory->raw(App\Voucher::class);

    return array_merge($voucher, [
        'currentstate' => 'printed',
    ]);
});

/**
 * Voucher with currentstate dispatched.
 */
$factory->defineAs(App\Voucher::class, 'dispatched', function ($faker) use ($factory) {
    $voucher = $factory->raw(App\Voucher::class);

    // Dispatched is the first state, so we can go with default values here.
    factory(App\VoucherState::class)->create();

    return array_merge($voucher, [
        'currentstate' => 'dispatched',
    ]);
});

$factory->define(App\VoucherState::class, function (Faker\Generator $faker) {

    // Factory adds initial values for first possible state (dispatched)
    // Overwrite when we create - other states.
    return [
        'transition' => 'dispatch',
        'from' => 'printed',
        'user_id' => 1,
        'voucher_id' => 1,
        'to' => 'dispatched',
        // Required by the state package we are using, but we don't use this field
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
        // TODO : This generated a duplicate: https://travis-ci.org/neontribe/ARCVService/builds/583632956
        // But metaphone will occasionally return 6 chars if end char is an X -> KS
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
$factory->define(App\Registration::class, function (Faker\Generator $faker, $attributes) {

  $eligibilities_hsbs = config('arc.reg_eligibilities_hsbs');
  $eligibilities_nrpf = config('arc.reg_eligibilities_nrpf');

    if (!empty($attributes['centre_id'])) {
        // Use the passed centre id.
        $centre = App\Centre::find($attributes['centre_id']);
    } else {
        // Default to a random centre.
        $centre = App\Centre::inRandomOrder()->first();
    }

    // Make a new one if we have NO centres already
    if (is_null($centre)) {
        $centre = factory(App\Centre::class)->create();
    }

    // if we weren't given a family, make one.
    $family = (empty($attributes['family_id']))
        ? factory(App\Family::class)->make()
        : App\Family::find($attributes['family_id'])
    ;

    // Set initial centre (and thus, rvid)
    $family->lockToCentre($centre);
    $family->save();

    // Add dependent models
    if ($family->carers->count() === 0) {
        $family->carers()->saveMany(factory(App\Carer::class, random_int(1, 3))->make());
    }

    if ($family->children->count() === 0) {
        $family->children()->saveMany(factory(App\Child::class, random_int(0, 4))->make());
    }

    return [
        'centre_id' => $centre->id,
        'family_id' => $family->id,
        'eligibility_hsbs' => $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)],
        'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
        'consented_on' => Carbon::now(),
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

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-6 years', '+9 months')->getTimestamp());
    $dob = $dob->startOfMonth();
    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

$factory->state(App\Child::class, 'verified', function(Faker\Generator $faker) {
    return ['verified' => true];
});

$factory->state(App\Child::class, 'unverified', function(Faker\Generator $faker) {
    return ['verified' => false];
});


// Child - unborn
$factory->defineAs(App\Child::class, 'unbornChild', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('+2 month', '+8 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - almost born
$factory->defineAs(App\Child::class, 'almostBorn', function (Faker\Generator $faker) {

    $dob = Carbon::now()->startOfMonth()->addMonths(1);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});


// Child - almost 1
$factory->defineAs(App\Child::class, 'almostOne', function (Faker\Generator $faker) {

    $dob = Carbon::now()->startOfMonth()->subMonths(11);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - under 1
$factory->defineAs(App\Child::class, 'underOne', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-10 months', '-2 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - Between One and Primary School Age
$factory->defineAs(App\Child::class, 'betweenOneAndPrimarySchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-32 months', '-14 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Primary School when the school_month rolls around
$factory->defineAs(App\Child::class, 'readyForPrimarySchool', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $dob = Carbon::now()->startOfMonth()->subYears(4);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - over School Age
$factory->defineAs(App\Child::class, 'isPrimarySchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-10 years', '-6 years')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Secondary School when the school_month rolls around
$factory->defineAs(App\Child::class, 'readyForSecondarySchool', function (Faker\Generator $faker) {

    // Make a child who's 11 now, and thus due to start school soon(ish)
    $dob = Carbon::now()->startOfMonth()->subYears(11);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - over Primary School Age
$factory->defineAs(App\Child::class, 'isSecondarySchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-17 years', '-12 years')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});


//Note
$factory->define(App\Note::class, function (Faker\Generator $faker) {

    return [
        'content' => 'this is some note content',
        'family_id' => 1,
        'user_id' => 1,
    ];
});

// StateToken - pretty empty, it generates it's own UUID
$factory->define(App\StateToken::class, function (Faker\Generator $faker, $attributes) {
        // Create a default UUID if you havn't got one.
        $uuid = (empty($attributes['uuid']))
            ? App\StateToken::generateUnusedToken()
            : $attributes['uuid'];

        return
            [
              'uuid' => $uuid
            ];
});

// Delivery - a schedule of vouchers sent somewhere
$factory->define(App\Delivery::class, function (Faker\Generator $faker, $attributes) {

    $centre_id = (empty($attributes['centre_id']))
        ? factory(App\Centre::class)->create()
        : $attributes['centre_id']
    ;

    $dispatched_at = (empty($attributes['dispatched_at']))
        ? Carbon::today()
        : $attributes['dispatched_at']
    ;

    return [
        'range' => '',
        'dispatched_at' => $dispatched_at,
        'centre_id' => $centre_id
    ];
});
