<?php

namespace Tests\Unit\Console\Commands;

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class ArcTestCoverageTest extends TestCase
{

    use CreatesApplication;

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
