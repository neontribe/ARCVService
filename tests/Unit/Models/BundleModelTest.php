<?php

namespace Tests\Unit\Models;

use App\Carer;
use App\Family;
use Auth;
use App\Bundle;
use App\Registration;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BundleModelTest extends TestCase
{

    use DatabaseMigrations;

    /** @var Bundle bundle */
    protected $bundle;
    protected function setUp(): void
    {
        parent::setUp();
        // create a blank factory
        $this->bundle = factory('App\Bundle')->create();
    }

    public function testBundleIsCreatedWithExpectedAttributes()
    {
        $b = $this->bundle;
        $this->assertInstanceOf(Bundle::class, $b);
        $this->assertIsInt($b->registration_id);
        $this->assertIsInt($b->entitlement);
        // a blank bundle hasn't been disbursed.
        $this->assertNull($b->collecting_carer_id);
        $this->assertNull($b->disbursing_centre_id);
        $this->assertNull($b->disbursing_user_id);
        $this->assertNull($b->disbursed_at);
        // a blank bundle doesn't have vouchers.
        $this->assertEmpty($b->vouchers);
    }

    public function testPopulatedBundleIsCreatedWithExpectedAttributes()
    {
        $centre = factory('App\Centre')->create();
        $user = factory('App\CentreUser')->create(['centre_id'=>$centre->id]);
        Auth::login($user);

        // family and carer needed to collect the bundle

        $family = factory(Family::class)->create(['initial_centre_id'=>$user->centre_id]);
        $carer = factory(Carer::class)->create(['name'=>'Bob','family_id'=>$family->id]);

        //create three vouchers and transition to collected.
        $vs = factory('App\Voucher', 'printed', 3)
            ->create()
            ->each(function ($v) {
                $v->applyTransition('dispatch');
                $v->applyTransition('collect');
                $v->bundle()->associate($this->bundle);
                $v->save();
            });
        //just to check we have the expected number of vouchers before continuing
        $this->assertEquals($vs->count(), $this->bundle->vouchers()->count());

        $disbursedBundle = $this->bundle;

        $disbursedBundle->disbursed_at = Carbon::now()->startOfDay();
        $disbursedBundle->collecting_carer_id = $carer->id;
        $disbursedBundle->disbursing_centre_id = $family->initial_centre_id;
        $disbursedBundle->disbursing_user_id = $user->id;

        $this->assertIsInt($disbursedBundle->collecting_carer_id);
        $this->assertIsInt($disbursedBundle->disbursing_centre_id);
        $this->assertIsInt($disbursedBundle->disbursing_user_id);
        $this->assertInstanceOf(Carbon::class, $disbursedBundle->disbursed_at);
        $this->assertNotEmpty($disbursedBundle->vouchers);
    }

    public function testBundleCanHaveManyVouchers()
    {
        $user = factory('App\CentreUser')->create();
        Auth::login($user);

        // Create three vouchers.
        $vs = factory('App\Voucher', 'printed', 3)
            ->create()
            ->each(function ($v) {
                $v->bundle()->associate($this->bundle);
                $v->save();
            });
        $this->assertEquals($vs->count(), $this->bundle->vouchers()->count());
    }

    /** @test */
    public function testItCanGetOnlyDisbursedBundles()
    {
        // Make a registration
        $registration = factory(Registration::class)->create();

        // Grab a Faker to make some random stuff later
        $faker = Factory::create();

        // Add a disbursed bundle history
        $disbursedBundles = factory('App\Bundle', 4)->create([
            'disbursed_at' => Carbon::yesterday()
                ->startOfDay()
                ->addHours(
                    // Make some wiggle on those hours
                    $faker->unique()->randomDigitNotNull()
                )
        ]);

        // Save the bundles
        $registration->bundles()->saveMany($disbursedBundles);
        $registration->bundles()->save($this->bundle);

        // Fresh and check the bundles
        $registration->refresh();
        $this->assertEquals($disbursedBundles->count(), $registration->bundles()->disbursed()->count());
    }

    /** @test */
    public function testItCannotAlterTheBundleToIncludeADisbursedVoucher()
    {
        $user = factory('App\CentreUser')->create();
        Auth::login($user);

        // Make a bundle
        /** @var Bundle $disbursedBundle */
        $disbursedBundle = factory(Bundle::class)->create();

        // Make some vouchers and add those to it.
        $vs = factory('App\Voucher', 'printed', 3)
            ->create()
            ->each(function ($v) use ($disbursedBundle) {
                $v->applyTransition('dispatch');
                $v->bundle()->associate($disbursedBundle);
                $v->save();
            });

        // Disburse it
        $disbursedBundle->disbursed_at = Carbon::now()->startOfDay();

        // See there are vouchers
        $this->assertEquals(3, $disbursedBundle->vouchers()->count());

        // Try to remove its vouchers by setting them to null
        $errors = $disbursedBundle->alterVouchers($vs, [], null);

        // See errors
        $this->assertArrayHasKey("disbursed", $errors);

        $codes = $vs->pluck("code")->toArray();
        foreach ($codes as $value) {
            $this->assertContains($value, $errors["disbursed"]);
        }

        // See it has the vouchers still
        $this->assertEquals(3, $disbursedBundle->vouchers()->count());

        // NOW, try to add the vouchers to our empty bundle, stealing them
        $errors = $this->bundle->alterVouchers($vs, [], $this->bundle);

        // See errors.
        $this->assertArrayHasKey("disbursed", $errors);

        $codes = $vs->pluck("code")->toArray();
        foreach ($codes as $value) {
            $this->assertContains($value, $errors["disbursed"]);
        }
        // See it has the vouchers still.
        $this->assertEquals(3, $disbursedBundle->vouchers()->count());

        // See the new bundle is still empty.
        $this->assertEquals(0, $this->bundle->vouchers()->count());
    }
}
