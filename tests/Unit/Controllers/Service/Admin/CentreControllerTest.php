<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\Sponsor;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;

class CentreControllerTest extends StoreTestCase
{
    use RefreshDatabase;

    private AdminUser $adminUser;

    private Sponsor $sponsor;

    private array $data;

    private Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));
        $this->adminUser = factory(AdminUser::class)->create();
        $this->sponsor = factory(Sponsor::class)->create();
        $this->data = [
            'name' => $this->faker->city,
            'sponsor_id' => $this->sponsor->id,
            'prefix' => strtoupper($this->faker->lexify(str_repeat('?', random_int(1, 5)))),
            'print_pref' => array_random(config('arc.print_preferences')),
            'can_collect' => random_int(0, 1)
        ];
    }


    public function testItCanStoreACentre(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(
                route('admin.centres.store'),
                $this->data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centres.index'))
            ->see($this->data["name"])
            ->see($this->sponsor->name)
            ->see($this->data["prefix"])
            ->see($this->data["print_pref"])
        ;
        // find the centre by prefix
        $c = Centre::where('prefix', $this->data['prefix'])->first();
        $this->assertNotNull($c);
    }


    public function testICanSeeAnEditButtonOnTheListOfCentres(): void
    {
        $centre = factory(Centre::class)->create();
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centres.index'))
            ->assertResponseOk()
            ->seeInElement('h1', 'Children\'s Centres')
            ->seeInElement('td', $centre->name)
            ->seeInElement('a', 'Edit')
        ;
    }

    public function testICanUpdateACentre(): void
    {
        $centre = factory(Centre::class)->create();
        $this->seeInDatabase('centres', [
            'id' => $centre->id,
            'name' => $centre->name
        ]);

        $data = array_merge($centre->getAttributes(), ['name' => 'New Centre Name']);
        $this->actingAs($this->adminUser, 'admin')
          ->put(
              route('admin.centres.update', ['centre' => $centre->id]),
              $data
          );
        $this->seeInDatabase('centres', [
            'id' => $centre->id,
            'name' => 'New Centre Name'
        ]);
        $this->dontSeeInDatabase('centres', [
            'id' => $centre->id,
            'name' => $centre->name
        ]);
    }
}
