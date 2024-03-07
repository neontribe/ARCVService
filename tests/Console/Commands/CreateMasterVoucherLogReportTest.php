<?php

namespace Tests\Console\Commands;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use DB;
use Mockery\Mock;
use PDO;
use PDOStatement;
use Tests\MysqlStoreTestCase;

class CreateMasterVoucherLogReportTest extends MysqlStoreTestCase
{

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testCommandOk()
    {
        $results = $this
            ->artisan("arc:createMVLReport")
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->execute();
        $this->assertEquals(0, $results);
    }

}
