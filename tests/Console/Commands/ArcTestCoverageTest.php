<?php

namespace Tests\Console\Commands;

use Facebook\WebDriver\Exception\Internal\RuntimeException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

class ArcTestCoverageTest extends TestCase
{

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testCommandOK()
    {
        $data = "<phpuint><project><directory><totals>" .
            "<lines percent='99' />" .
            "</totals></directory></project></phpuint>";
        $tmp = tempnam(sys_get_temp_dir(), "mockedxml_");
        file_put_contents($tmp, $data);

        $results = $this->artisan("arc:test:coverage " . $tmp)->execute();
        $this->assertEquals(0, $results);
    }

    public function testCommandNoArg()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->artisan("arc:test:coverage")->execute();
    }

    public function testCommandFails()
    {
        $data = "<phpuint><project><directory><totals>" .
            "<lines percent='20' />" .
            "</totals></directory></project></phpuint>";
        $tmp = tempnam(sys_get_temp_dir(), "mockedxml_");
        file_put_contents($tmp, $data);

        $results = $this->artisan("arc:test:coverage " . $tmp)->execute();
        $this->assertEquals(-1, $results);
    }

    public function testCommandTestDifferentAcceptance()
    {
        $data = "<phpuint><project><directory><totals>" .
            "<lines percent='50' />" .
            "</totals></directory></project></phpuint>";
        $tmp = tempnam(sys_get_temp_dir(), "mockedxml_");
        file_put_contents($tmp, $data);

        $results = $this->artisan("arc:test:coverage " . $tmp . " 40")->execute();
        $this->assertEquals(0, $results);
    }
}
