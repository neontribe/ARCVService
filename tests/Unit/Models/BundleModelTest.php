<?php

namespace Tests;

use Auth;
use App\Bundle;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BundleModelTest extends TestCase
{

    use DatabaseMigrations;

    protected $bundle;
    protected function setUp()
    {
        parent::setUp();
        // create a blank factory
        $this->bundle = factory('App\Bundle')->create();
    }

    public function testBundleIsCreatedWithExpectedAttributes()
    {
        $b = $this->bundle;
        $this->assertInstanceOf(Bundle::class, $b);
        $this->assertInternalType('integer', $b->registration_id);
        $this->assertInternalType('integer', $b->entitlement);
        // a blank bundle hasn't been disbursed.
        $this->assertNull($b->collecting_carer_id);
        $this->assertNull($b->disbursing_centre_id);
        $this->assertNull($b->disbursing_user_id);
        $this->assertNull($b->disbursed_at);
        // a blank bundle doesn't have vouchers.
        $this->assertEmpty($b->vouchers);
    }

    public function testBundleCanHaveManyVouchers()
    {
        $user = factory('App\CentreUser')->create();
        Auth::login($user);

        // Create three vouchers and transition to printed.
        $vs = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function ($v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->bundle()->associate($this->bundle);
                $v->save();
            });
        $this->assertEquals($vs->count(), $this->bundle->vouchers()->count());
    }
}
