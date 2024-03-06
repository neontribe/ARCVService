<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\CentreUser;
use App\Sponsor;
use Artisan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class AddCentreTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testCommand()
    {
        $count = Sponsor::all()->count();
        print("Sponsors count = " . $count . "\n");

        $sponsor = new Sponsor();
        $sponsor->name = "Mr Bolt";
        $sponsor->shortcode = "TR";
        $sponsor->save();

        $centreUser = new CentreUser();
        $centreUser->name = "Barney McGrew";
        $centreUser->email = "b.mcgrew@example.com";
        $centreUser->password = "password";
        $centreUser->role = "foo";
        $centreUser->save();

        $args = sprintf(
            "%s %s %s %s %s",
            "Trumpton", # name
            "NA", # prefix
            "TR", # shortcode
            "individual", # pref
            $centreUser->email # email
        );
        $cli = "arc:addCentre " . $args;
        $this->withoutMockingConsoleOutput()->artisan("arc:addCentre " . $args);
        $result = Artisan::output();
        $this->assertEquals(
            "Starting voucher export from 2022/04/01 to 2023/03/31 in chunks of 54321.\n",
            $result
        );
    }
}
