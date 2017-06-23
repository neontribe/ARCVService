<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WebRoutesTest extends TestCase
{
    /**
     * Verify content on dashboard.
     *
     * @return void
     */
    public function testDashboardPage()
    {
        $this->get('/')
            ->assertStatus(200)
        ;
    }
}
