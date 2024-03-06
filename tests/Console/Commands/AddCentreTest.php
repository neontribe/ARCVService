<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\CentreUser;
use Faker\Factory;
use App\Sponsor;
use Artisan;
use Faker\Generator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

/**
 * @property Generator $faker
 */
class AddCentreTest extends TestCase
{
    use DatabaseMigrations;

    private Generator $faker;
    private Centre $centre;
    private CentreUser $centreUser;
    private Sponsor $sponsor;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));
        $this->centre = factory(Centre::class)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        $this->sponsor = factory(Sponsor::class)->create();
    }

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testCommand()
    {
        $args = sprintf(
            "%s %s %s %s %s",
            "Trumpton", # name
            "NA", # prefix
            $this->sponsor->shortcode, # shortcode
            $this->centre->print_pref,
            $this->centreUser->email # email
        );
        $this
            ->artisan("arc:addCentre " . $args)
            ->expectsConfirmation('Do you wish to continue?', 'yes');
        $result = Artisan::output();
        $this->assertEquals(
            "Starting voucher export from 2022/04/01 to 2023/03/31 in chunks of 54321.\n",
            $result
        );
    }
}
