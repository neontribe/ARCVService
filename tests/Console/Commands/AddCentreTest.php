<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\CentreUser;
use App\Sponsor;
use Artisan;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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

    public function testCommandOk()
    {
        $args = sprintf(
            "%s %s %s %s %s",
            "Trumpton", # name
            "NA", # prefix
            $this->sponsor->shortcode, # shortcode
            $this->centre->print_pref,
            $this->centreUser->email # email
        );
        $results = $this
            ->artisan("arc:addCentre " . $args)
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->execute();

        $this->assertEquals(0, $results);
    }

    public function testCommandNoUser()
    {
    }

    public function testCommandNoSponsor()
    {
    }

    public function testCommandCenterExists()
    {
    }

    public function testCommandPreferenceDoesNotExist()
    {
    }

    public function testCommandUserWarningDenied()
    {
    }

    public function testCommandFailedLoggedIn()
    {
    }
}
