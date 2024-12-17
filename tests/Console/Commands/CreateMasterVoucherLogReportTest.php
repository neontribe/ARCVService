<?php

namespace Tests\Console\Commands;

use Tests\MysqlStoreTestCase;
use Tests\CreatesApplication;

class CreateMasterVoucherLogReportTest extends MysqlStoreTestCase
{
    use CreatesApplication;


    public function testCommandOk(): void
    {
        $results = $this
            ->artisan("arc:createMVLReport", ["--force" => true]);
        $this->assertEquals(0, $results);
    }

}
