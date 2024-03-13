<?php

namespace Tests\Unit\Console\Commands;

use Tests\CreatesApplication;
use Tests\MysqlStoreTestCase;

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
