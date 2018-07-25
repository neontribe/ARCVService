<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Sponsor;
use App\Voucher;
use App\Centre;

class SponsorModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $sponsor;
    protected function setUp()
    {
        parent::setUp();
        $this->sponsor = factory(Sponsor::class)->create();
    }

    public function testSponsorIsCreatedWithExpectedAttributes()
    {
        $s = $this->sponsor;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Sponsor::class, $s);
        $this->assertNotNull($s->name);
    }


    public function testSoftDeleteSponsor()
    {
        $this->sponsor->delete();
        $this->assertCount(1, Sponsor::withTrashed()->get());
        $this->assertCount(0, Sponsor::all());
    }

    public function testSponsorHasManyVouchers()
    {
        factory(Voucher::class, 10)->create([
            'sponsor_id' => $this->sponsor->id,
        ]);
        factory(Voucher::class, 2)->create([
            'sponsor_id' => $this->sponsor->id +1,
        ]);
        $this->assertCount(10, $this->sponsor->vouchers);
        $this->assertNotEquals($this->sponsor->vouchers, Voucher::all());
    }
    /** @test */
    public function itHasExpectedAttributes()
    {
        $sponsor = factory(Sponsor::class)->make();
        $this->assertNotNull($sponsor->name);
        $this->assertNotNull($sponsor->shortcode);
    }

    /** @test */
    public function itCanHaveCentres()
    {
        // Make a sponsor
        $sponsor = factory(Sponsor::class)->create();
        // These should auto associate with the only Sponsor
        $centres = factory(Centre::class, 2)->create();
        $sponsor->fresh();

        // Check it's got centres
        $this->assertNotNull($sponsor->centres);

        // Check the expected associations
        $this->assertEquals(2, $sponsor->centres->count());

        // Check they really are the same Centres
        foreach ($centres as $index => $centre) {
            $this->assertEquals($centres[$index]->name, $centre->name);
        }
    }
}
