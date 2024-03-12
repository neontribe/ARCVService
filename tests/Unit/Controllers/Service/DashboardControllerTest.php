<?php

namespace Tests\Unit\Controllers\Service;

use App\Http\Controllers\Service\DashboardController;
use Illuminate\View\View;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function testDashboard()
    {
        // TODO I can't find  App\Http\Controllers\Service\DashboardController in the routes
        // is it actually used?
        // $response = $this->get(route('????'))->assertStatus(200);

        $dc = new DashboardController();
        $response = $dc->index();
        $this->assertInstanceOf(View::class, $response);
    }
}
