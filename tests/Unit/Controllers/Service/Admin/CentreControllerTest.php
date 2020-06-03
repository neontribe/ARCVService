<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\Sponsor;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CentreControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Sponsor $sponsor */
    private $sponsor;

    /** @var array $data */
    private $data;

    /** @var Generator $faker */
    private $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));
        $this->adminUser = factory(AdminUser::class)->create();
        $this->sponsor = factory(Sponsor::class)->create();
        $this->data = [
            'name' => $this->faker->city,
            'sponsor' => $this->sponsor->id,
            'rvid_prefix' => strtoupper($this->faker->lexify(str_repeat('?', rand(1, 5)))),
            'print_pref' => array_random(config('arc.print_preferences'))
        ];
    }

    /** @test */
    public function testItCanStoreACentre()
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
            ->see($this->data["rvid_prefix"])
            ->see($this->data["print_pref"])
        ;
        // find the centre by prefix
        $c = Centre::where('prefix', $this->data['rvid_prefix'])->first();
        $this->assertNotNull($c);
    }
}
