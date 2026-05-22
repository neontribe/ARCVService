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
        'role' => 'centre_user',
    ];
});

/**
 * CentreUser in the retired state.
 * PII is wiped, retired_at is set, and the model is soft-deleted.
 * Relations (notes, centres) are preserved on the underlying row.
 */
$factory->afterCreatingState(App\CentreUser::class, 'retired', function ($centreUser) {
    // retired centres must be soft deleted first
    $centreUser->delete();
    $centreUser->retire();
});

/**
 * CentreUser who can Download.
 */
$factory->state(App\CentreUser::class, 'withDownloader', function ($faker) use ($factory) {
    $cu = $factory->raw(App\CentreUser::class);

    return array_merge($cu, [
        'downloader' => true,
    ]);
});

/**
 * Specifically an Admin Centre User [foodmatters_user]
 */
$factory->state(App\CentreUser::class, 'FMUser', function (Faker\Generator $faker) use ($factory) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'role' => 'foodmatters_user',
        'downloader' => true,
    ];
});


/**
 * Sponsor for testing
 */
$factory->define(App\Sponsor::class, function (Faker\Generator $faker, array $attributes = []) {

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

    $data = [
        'name' => $counties[$index],
        'shortcode' => $faker->regexify('[A-Z]{4}'),
    ];

    return array_merge($attributes, $data);
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
$factory->state(App\Trader::class, 'withnullable', function ($faker) use ($factory) {
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
        : factory(App\Registration::class)->create();

    // get/calculate and stash the entitlement
    $entitlement = isset($attributes['entitlement'])
        ? $attributes['entitlement']
        : $registration->getValuation()->getEntitlement();

    return [
        'registration_id' => $registration->id,
        'entitlement' => $entitlement,
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
            'name' => 'Null Sponsors Inc.',
        ]);
        $sponsor_id = $sponsor->id;
    }
    $states = config('state-machine.Voucher.states');
    $currentstate = $faker->randomElement($states);

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
 * Helper: insert one VoucherState row for the given transition.
 *
 * The 'from' value is read from the state-machine config so this stays in
 * sync with the real transition definitions.  Pass $fromOverride when the
 * transition allows multiple 'from' states and you need a specific one
 * (e.g. 'retire' can come from either 'voided' or 'expired').
 */
$makeVoucherState = static function (App\Voucher $voucher, string $transition, string $fromOverride = null) {
    $def = config("state-machine.Voucher.transitions.{$transition}");
    factory(App\VoucherState::class)->create([
        'voucher_id' => $voucher->id,
        'transition' => $transition,
        'from' => $fromOverride ?? (is_array($def['from']) ? $def['from'][0] : $def['from']),
        'to' => $def['to'],
    ]);
};

/**
 * printed — the initial state; no transition history is needed.
 */
$factory->state(App\Voucher::class, 'printed', function ($faker) {
    return ['currentstate' => 'printed'];
});

/**
 * dispatched — printed → dispatched
 */
$factory->state(App\Voucher::class, 'dispatched', function ($faker) {
    return ['currentstate' => 'dispatched'];
});
$factory->afterCreatingState(App\Voucher::class, 'dispatched', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
});

/**
 * recorded — printed → dispatched → recorded
 */
$factory->state(App\Voucher::class, 'recorded', function ($faker) {
    return ['currentstate' => 'recorded'];
});
$factory->afterCreatingState(App\Voucher::class, 'recorded', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'collect', 'dispatched');
});

/**
 * payment_pending — printed → dispatched → recorded → payment_pending
 */
$factory->state(App\Voucher::class, 'payment_pending', function ($faker) {
    return ['currentstate' => 'payment_pending'];
});
$factory->afterCreatingState(App\Voucher::class, 'payment_pending', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'collect', 'dispatched');
    $makeVoucherState($voucher, 'confirm');
});

/**
 * reimbursed — printed → dispatched → recorded → payment_pending → reimbursed
 */
$factory->state(App\Voucher::class, 'reimbursed', function ($faker) {
    return ['currentstate' => 'reimbursed'];
});
$factory->afterCreatingState(App\Voucher::class, 'reimbursed', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'collect', 'dispatched');
    $makeVoucherState($voucher, 'confirm');
    $makeVoucherState($voucher, 'payout');
});

/**
 * voided — printed → dispatched → voided
 */
$factory->state(App\Voucher::class, 'voided', function ($faker) {
    return ['currentstate' => 'voided'];
});
$factory->afterCreatingState(App\Voucher::class, 'voided', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'void');
});

/**
 * expired — printed → dispatched → expired
 */
$factory->state(App\Voucher::class, 'expired', function ($faker) {
    return ['currentstate' => 'expired'];
});
$factory->afterCreatingState(App\Voucher::class, 'expired', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'expire');
});

/**
 * retired-from-voided — printed → dispatched → voided → retired
 */
$factory->state(App\Voucher::class, 'retired-from-voided', function ($faker) {
    return ['currentstate' => 'retired'];
});
$factory->afterCreatingState(App\Voucher::class, 'retired-from-voided', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'void');
    $makeVoucherState($voucher, 'retire', 'voided');
});

/**
 * retired-from-expired — printed → dispatched → expired → retired
 */
$factory->state(App\Voucher::class, 'retired-from-expired', function ($faker) {
    return ['currentstate' => 'retired'];
});
$factory->afterCreatingState(App\Voucher::class, 'retired-from-expired', function ($voucher) use ($makeVoucherState) {
    $makeVoucherState($voucher, 'dispatch');
    $makeVoucherState($voucher, 'expire');
    $makeVoucherState($voucher, 'retire', 'expired');
});

/**
 * VoucherState — base defaults model the first possible transition (dispatch).
 * Individual rows are normally created via the $makeVoucherState helper above,
 * which overrides transition/from/to for each step in a voucher's history.
 */
$factory->define(App\VoucherState::class, function (Faker\Generator $faker) {
    return [
        'transition' => 'dispatch',
        'from' => 'printed',
        'to' => 'dispatched',
        'user_id' => 1,
        'voucher_id' => 1,
        // Required by the state package; not used by the application.
        'source' => 'factory',
    ];
});


/**
 *  Models for CC Testing
 */

// Centre, with random sponsor
$factory->define(App\Centre::class, function (Faker\Generator $faker) {

    $sponsors = App\Sponsor::get();

    $sponsor = ($sponsors->count() > 0)
        ? $sponsors->random()
        : factory(App\Sponsor::class)->create();

    $name = $faker->unique()->streetName;
    $prefix = $faker->unique()->lexify('?????');

    return [
        'name' => $name,
        'prefix' => $prefix,
        'sponsor_id' => $sponsor->id,
        // print_pref will be 'collection' by default.
        // To ensure we always have one 'individual', adding to seeder as well.
        'print_pref' => $faker->randomElement(['individual', 'collection']),
        'can_collect' => false,
    ];
});

$factory->state(App\Centre::class, 'collecting', function (Faker\Generator $faker) {
    return ['can_collect' => true];
});

/**
 * Deleted centre — models the end-state of a centre that has been deactivated and removed.
 *
 * What this builds:
 *  - A centre user associated only with this centre (will be soft-deleted)
 *  - A registration with a family (carers + children) as a historic record
 *  - A bundle for that registration (will be removed, voucher links nullified first)
 *  - Family marked as left with leaving_reason 'centre_deleted'
 *  - Vouchers detached from bundle to preserve voucher/state history
 *  - Bundle and registration deleted
 *  - Centre user soft-deleted (no remaining centre association)
 *  - Centre itself soft-deleted
 */
/**
 * Deleted centre state — no attributes to set upfront.
 * afterCreatingState below owns the full lifecycle, ending with the soft delete.
 */
$factory->state(App\Centre::class, 'deleted', function () {
    return [];
});

$factory->afterCreatingState(App\Centre::class, 'deleted', function (App\Centre $centre) {
    // 1. Create a centre user homed only at this centre
    $centreUser = factory(App\CentreUser::class)->create(['centre_id' => $centre->id]);
    $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

    // 2. Create a registration (also builds a family with carers and children)
    $registration = factory(App\Registration::class)->create(['centre_id' => $centre->id]);
    $family = $registration->family;

    // 3. Create a bundle against the registration
    $bundle = factory(App\Bundle::class)->create(['registration_id' => $registration->id]);

    // 4. Mark the family as left
    $family->leaving_on = Carbon::now();
    $family->leaving_reason = 'centre_deleted';
    $family->save();

    // 5. Nullify bundle_id on any vouchers to preserve voucher/state history
    //    before the bundle is removed
    App\Voucher::where('bundle_id', $bundle->id)->update(['bundle_id' => null]);

    // 6. Remove bundle then registration (order matters for FK constraints).
    //    Registration has no SoftDeletes — this is a hard delete.
    $bundle->delete();
    $registration->delete();

    // 7. Soft-delete the centre user — they have no other centre association
    $centreUser->delete();

    // 8. Soft-delete the centre — natural final step of the deactivation sequence
    $centre->delete();
});


// Registration
$factory->define(App\Registration::class, function (Faker\Generator $faker, $attributes) {

    $eligibilities_hsbs = config('arc.reg_eligibilities_hsbs');
    $eligibilities_nrpf = config('arc.reg_eligibilities_nrpf');
    $eligibility_hsbs = $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)];
    $eligible_from = null;
    if ($eligibility_hsbs === 'healthy-start-receiving') {
        $eligible_from = Carbon::now();
    }

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
        : App\Family::find($attributes['family_id']);

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
        'eligibility_hsbs' => $eligibility_hsbs,
        'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
        'consented_on' => Carbon::now(),
        'eligible_from' => $eligible_from,
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
        'name' => $faker->firstName . " " . $faker->lastName,
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

$factory->state(App\Child::class, 'verified', function (Faker\Generator $faker) {
    return ['verified' => true];
});

$factory->state(App\Child::class, 'unverified', function (Faker\Generator $faker) {
    return ['verified' => false];
});


// Child - unborn
$factory->state(App\Child::class, 'unbornChild', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('+2 month', '+8 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - almost born
$factory->state(App\Child::class, 'almostBorn', function (Faker\Generator $faker) {

    $dob = Carbon::now()->startOfMonth()->addMonths(1);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});


// Child - almost 1
$factory->state(App\Child::class, 'almostOne', function (Faker\Generator $faker) {

    $dob = Carbon::now()->startOfMonth()->subMonths(11);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - under 1
$factory->state(App\Child::class, 'underOne', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-10 months', '-2 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - Between One and Primary School Age
$factory->state(App\Child::class, 'betweenOneAndPrimarySchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-32 months', '-14 months')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Primary School when the school_month rolls around
$factory->state(App\Child::class, 'readyForPrimarySchool', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $dob = Carbon::now()->startOfMonth()->subYears(4);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Primary School when the school_month rolls around ( QUESTION )
$factory->state(App\Child::class, 'readyForScottishPrimarySchool', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $dob = Carbon::now()->month(1)->startOfMonth()->subYears(4);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Primary School in Scotland, but will still be four.
$factory->state(App\Child::class, 'canDefer', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $now = Carbon::now()->startOfMonth()->subYears(4);
    $year = $now->year;
    $dob = Carbon::now();
    $schoolStartMonth = config('arc.scottish_school_month');
    $dob->year($year)->subMonths($schoolStartMonth - 6)->day(1);
    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Primary School in Scotland, but won't be four.
$factory->state(App\Child::class, 'canNotDefer', function (Faker\Generator $faker) {

    // Make a child who's four now, and thus due to start school soon(ish)
    $now = Carbon::now()->startOfMonth()->subYears(5);
    $year = $now->year;
    $dob = Carbon::now();
    $schoolStartMonth = config('arc.scottish_school_month');
    $dob->year($year)->day(1);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - over School Age
$factory->state(App\Child::class, 'isPrimarySchoolAge', function (Faker\Generator $faker) {

    $dob = Carbon::createFromTimestamp($faker->dateTimeBetween('-10 years', '-6 years')->getTimestamp());
    $dob = $dob->startOfMonth();

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - ready for Secondary School when the school_month rolls around
$factory->state(App\Child::class, 'readyForSecondarySchool', function (Faker\Generator $faker) {

    // Make a child who's 11 now, and thus due to start school soon(ish)
    $dob = Carbon::now()->startOfMonth()->subYears(11);

    return [
        'born' => $dob->isPast(),
        'dob' => $dob->toDateTimeString(),
    ];
});

// Child - over Primary School Age
$factory->state(App\Child::class, 'isSecondarySchoolAge', function (Faker\Generator $faker) {

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
            'uuid' => $uuid,
        ];
});

// Delivery - a schedule of vouchers sent somewhere
$factory->define(App\Delivery::class, function (Faker\Generator $faker, $attributes) {

    $centre_id = (empty($attributes['centre_id']))
        ? factory(App\Centre::class)->create()
        : $attributes['centre_id'];

    $dispatched_at = (empty($attributes['dispatched_at']))
        ? Carbon::today()
        : $attributes['dispatched_at'];

    return [
        'range' => '',
        'dispatched_at' => $dispatched_at,
        'centre_id' => $centre_id,
    ];
});
