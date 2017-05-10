<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class WebRoutesTest extends TestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testLandingPage()
    {
        $this->get('/')->assertStatus(200);
    }
}
