<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Market;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MarketsControllerTest extends TestCase
{
    use DatabaseMigrations;

    private AdminUser $adminUser;
    private Sponsor $sponsor;
    private Market $market;
    private string $marketsStore;
    private string $marketsUpdate;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->sponsor = factory(Sponsor::class)->create();
        $this->market = factory(Market::class)->create();
    }

    public function testStore()
    {
        $name = "Earth Defense Directorate";
        $message = "Give your vouchers to Dr. Theopolis";

        $market = Market::where("name", '=', $name)->first();
        $this->assertNull($market);

        $this->actingAs($this->adminUser, 'admin')
            ->post(
                route('admin.markets.store'),
                [
                    "sponsor" => $this->sponsor->id,
                    "name" => $name,
                    "payment_message" => $message,
                ]
            )->assertRedirectToRoute('admin.markets.index');

        $market = Market::where("name", '=', $name)->first();
        $this->assertEquals($name, $market->name);
        $this->assertEquals($message, $market->payment_message);
    }


    public function testUpdate()
    {
        $name = "Earth Defense Directorate";
        $message = "Give your vouchers to Dr. Theopolis";

        $m = new Market([
            'name' => $name,
            'sponsor_id' => $this->sponsor->id,
            'location' => $this->sponsor->name,
            'payment_message' => $message
        ]);
        $m->save();

        $market = Market::where("name", '=', $name)->first();
        $this->assertNotNull($market);

        $name = "EDF";
        $message = "Give your vouchers to Twiki";
        $this->actingAs($this->adminUser, 'admin')
            ->put(
                route('admin.markets.update', ["id" => $market->id]),
                [
                    "sponsor" => $this->sponsor->id,
                    "name" => $name,
                    "payment_message" => $message,
                ]
            )->assertRedirectToRoute('admin.markets.index');

        $market = Market::where("name", '=', $name)->first();
        $this->assertEquals($name, $market->name);
        $this->assertEquals($message, $market->payment_message);
    }
}
