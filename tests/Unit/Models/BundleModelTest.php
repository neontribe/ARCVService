<?php

namespace Tests;

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
        $this->bundle = factory(Bundle::class)->create();
    }

    public function testBundleIsCreatedWithExpectedAttributes()
    {
        $b = $this->bundle;
        $this->assertInstanceOf(Bundle::class, $b);
        $this->assertInternalType('integer', $b->family_id);
        $this->assertInternalType('integer', $b->entitlement);
        $this->assertInternalType('integer', $b->centre_id);
        $this->assertNull($b->allocated_at);
        $this->assertEmpty($b->vouchers);
    }

    public function testBundleCanHaveManyVouchers()
    {
    }

    public function testBundleCannotHaveNoVouchers()
    {
    }

    public function testBundleBelongsToRegistration()
    {
    }

    public function testBundleCanBeAllocated()
    {
    }
}
