<?php

namespace Tests\Console\Commands;

use App\AdminUser;
use App\Sponsor;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;

class LegacyImportTest extends TestCase
{

    use DatabaseMigrations;

    private Generator $faker;
    private AdminUser $adminUser;
    private Sponsor $sponsor;
    private string $vouchersFilename;
    private string $noVouchersFilename;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));

        $this->sponsor = factory(Sponsor::class)->create();
        $this->adminUser = factory(AdminUser::class)->create();

        $_data = [];
        for ($i=0; $i<10; $i++) {
            $_data[] = sprintf("RVNT%05d", $i);
        }
        $data = implode("\n", $_data);
        $this->vouchersFilename = tempnam(sys_get_temp_dir(), "mockedxml_");
        file_put_contents($this->vouchersFilename, $data);

        $this->noVouchersFilename = tempnam(sys_get_temp_dir(), "mockedxml_");
        file_put_contents($this->noVouchersFilename, "");

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
            ->artisan("arc:legacyimport " .
                sprintf(
                    "%s %s %s",
                    $this->vouchersFilename,
                    $this->sponsor->shortcode,
                    $this->adminUser->email
                ))
            ->expectsConfirmation("Do you wish to continue?", "yes")
            ->execute();
        $this->assertEquals(0, $results);
    }


    public function testCommandInvalidAdminUser()
    {
        $results = $this
            ->artisan("arc:legacyimport " .
                sprintf(
                    "%s %s %s",
                    $this->vouchersFilename,
                    $this->sponsor->shortcode,
                    "no_user@example.com"
                ))
            ->execute();
        $this->assertEquals(1, $results);
    }

    public function testCommandNoSponsor()
    {
        $results = $this
            ->artisan("arc:legacyimport " .
                sprintf(
                    "%s %s %s",
                    $this->vouchersFilename,
                    "NO_A_CODE",
                    $this->adminUser->email
                ))
            ->execute();
        $this->assertEquals(2, $results);
    }
    public function testCommandNoCodes()
    {
        $results = $this
            ->artisan("arc:legacyimport " .
                sprintf(
                    "%s %s %s",
                    $this->noVouchersFilename,
                    $this->sponsor->shortcode,
                    $this->adminUser->email
                ))
            ->execute();
        $this->assertEquals(3, $results);
    }

    public function testCommandUserWarningDenied()
    {
        $results = $this
            ->artisan("arc:legacyimport " .
                sprintf(
                    "%s %s %s",
                    $this->vouchersFilename,
                    $this->sponsor->shortcode,
                    $this->adminUser->email
                ))
            ->expectsConfirmation("Do you wish to continue?", "no")
            ->execute();
        $this->assertEquals(4, $results);
    }
}
