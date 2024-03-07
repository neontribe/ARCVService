<?php

namespace Tests\Console\Commands;

use App\CentreUser;
use App\Sponsor;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * @property Generator $faker
 */
class AddSponsorTest extends TestCase
{
    use DatabaseMigrations;

    private Generator $faker;
    private CentreUser $centreUser;
    private Sponsor $sponsor;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));
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
        $results = $this
            ->artisan("arc:addSponsor " .
                sprintf(
                    "%s %s %s",
                    "Trumpton",
                    "NEW",
                    $this->centreUser->email
                ))
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->execute();
        $this->assertEquals(0, $results);
    }

    public function testCommandNoUser()
    {
        $results = $this
            ->artisan("arc:addSponsor " .
                sprintf(
                    "%s %s %s",
                    "Trumpton",
                    "NEW",
                    "not@real.user"
                ))
            ->execute();
        $this->assertEquals(1, $results);
    }

    public function testCommandNoSponsor()
    {
        $results = $this
            ->artisan("arc:addSponsor " .
                sprintf(
                    "%s %s %s",
                    "Trumpton",
                    $this->sponsor->shortcode,
                    $this->centreUser->email
                ))
            ->execute();
        $this->assertEquals(2, $results);
    }


    public function testCommandUserWarningDenied()
    {
        $results = $this
            ->artisan("arc:addSponsor " .
                sprintf(
                    "%s %s %s",
                    "Trumpton",
                    "NEW",
                    $this->centreUser->email
                ))
            ->expectsConfirmation('Do you wish to continue?', 'no')
            ->execute();
        $this->assertEquals(3, $results);
    }

    public function testCommandFailedLoggedIn()
    {
        Auth::shouldReceive('login')->once();
        Auth::shouldReceive('check')->once()->andreturn(false);
        $results = $this
            ->artisan("arc:addSponsor " .
                sprintf(
                    "%s %s %s",
                    "Trumpton",
                    "NEW",
                    $this->centreUser->email
                ))
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->execute();
        $this->assertEquals(4, $results);
    }
}
