<?php

namespace Tests\Unit\Controllers\Store;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class ExceptionHandlerTest extends StoreTestCase
{

    use DatabaseMigrations;

    protected function setUp()
    {
        //
    }

    /** @test  */
    public function itDoesNotStackTraceInProduction()
    {
        // set "production" mode
        // for each stacktrace in handler#s list
        //   call an exception
        //   assert log has one extra line
    }
}
