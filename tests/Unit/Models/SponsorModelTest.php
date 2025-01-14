<?php

namespace Tests\Unit\Models;

use App\Evaluation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Sponsor;
use App\Voucher;
use App\Centre;

class SponsorModelTest extends TestCase
{
    use RefreshDatabase;

    protected $sponsor;
    protected function setUp(): void
    {
        parent::setUp();
        $this->sponsor = factory(Sponsor::class)->create()->fresh();
    }

    public function testSponsorIsCreatedWithExpectedAttributes()
    {
        $s = $this->sponsor;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Sponsor::class, $s);
        $this->assertIsString($s->name);
        $this->assertIsString($s->shortcode);
        $this->assertFalse($s->can_tap);
        $this->assertIsInt($s->programme);
    }

    public function testItCanGetItsProgramName()
    {
        $s = $this->sponsor;
        $this->assertEquals($s->programme_name, config('arc.programmes')[$s->programme]);
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
    public function itCanHaveCentres()
    {
        // Make a sponsor
        $s = $this->sponsor;
        // Create a center, will auto associate to sponsor
        $centres = factory(Centre::class, 2)->create();
        $s->fresh();

        // Check it's got centres
        $this->assertNotNull($s->centres);

        // Check the expected associations
        $this->assertEquals(2, $s->centres->count());

        // Check they really are the same Centres
        foreach ($centres as $index => $centre) {
            $this->assertEquals($centres[$index]->name, $centre->name);
        }
    }

    /** @test */
    public function itCanHaveEvaluations()
    {
        // Make a sponsor
        $s = $this->sponsor;

        // create 2, junk but valid evaluations
        $evaluations = collect([
            new Evaluation([
               "name" => "a test name",
               "value" => 2,
               "entity" => "App\Child",
               "purpose" => "tests"
            ]),
            new Evaluation([
                "name" => "a test name2",
                "value" => 2,
                "entity" => "App\Child",
                "purpose" => "tests"
            ])
        ]);

        $this->sponsor->evaluations()->saveMany($evaluations);
        $this->sponsor->fresh();
        $this->assertCount(2, $this->sponsor->evaluations);
    }
}
