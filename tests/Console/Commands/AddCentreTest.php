<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\CentreUser;
use App\Sponsor;
use Artisan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;

class AddCentreTest extends TestCase
{
    use DatabaseMigrations;

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testCommand()
    {
        $centreUser = CentreUser::first();
        $sponsor = Sponsor::first();
        $centre = Centre::first();
        $args = sprintf(
            "%s %s %s %s %s",
            "Trumpton",
            "NA",
            $sponsor->shortcode,
            "individual",
            $centreUser->email,
        );
        $this->withoutMockingConsoleOutput()->artisan("arc:addCentre " . $args);
        $result = Artisan::output();
        $this->assertEquals(
            "Starting voucher export from 2022/04/01 to 2023/03/31 in chunks of 54321.\n",
            $result
        );
    }
}
