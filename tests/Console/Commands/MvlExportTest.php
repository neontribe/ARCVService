<?php

namespace Tests\Console\Commands;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;

class MvlExportTest extends TestCase
{
    use DatabaseMigrations;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testParameters(): void
    {
        $this->artisan('arc:mvl:export')
            ->assertExitCode(0)
            ->expectsOutput('Starting voucher export from 1970/01/01 to 2023/08/31 in chunks of 100000.');

        $params = [
            "--from" => "01/04/2022",
            "--to" => "31/03/2023",
            "--chunk-size" => "54321",
        ];
        $this->withoutMockingConsoleOutput()->artisan("arc:mvl:export", $params);
        $result = Artisan::output();

        $this->assertStringContainsString("2022/04/01", $result);
        $this->assertStringContainsString("2023/03/31", $result);
        $this->assertStringContainsString("54321", $result);
    }

}
