<?php

namespace Tests\Console\Commands;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use DB;
use Mockery\Mock;
use PDO;
use PDOStatement;
use Tests\MysqlStoreTestCase;
use Tests\CreatesApplication;

class CreateMasterVoucherLogReportTest extends MysqlStoreTestCase
{
    use CreatesApplication;


    public function testCommandOk()
    {
        $results = $this
            ->artisan("arc:createMVLReport")
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->execute();
        $this->assertEquals(0, $results);
    }

}
