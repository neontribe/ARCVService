<?php

namespace Tests\Unit\Controllers\Service\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    public function testLoginGood()
    {
        // Called in middleware
        Auth::shouldReceive("guard->check")->once()->andReturn(false);
        // Called in controller
        Auth::shouldReceive("guard->attempt")->once()->andReturn(true);

        /**
         * @var RedirectResponse $response
         */
        $response = $this->post(
            route('admin.login'),
            [
                'email' => 'twiki@example.com',
                'password' => 'bdbdbd',
            ]
        );
        $this->assertEquals(302, $response->status());
        $a = route('admin.dashboard');
        $b = $response->getTargetUrl();
        $this->assertEquals(route('admin.dashboard'), $response->getTargetUrl());
    }

    public function testLoginInvalidForm()
    {
        // Called in middleware
        Auth::shouldReceive("guard->check")->once()->andReturn(false);

        /**
         * @var RedirectResponse $response
         */
        $response = $this->post(
            route('admin.login'),
            [
                'fu' => 'bar'
            ]
        );
        $this->assertEquals(302, $response->status());
        $a = route('admin.dashboard');
        $b = $response->getTargetUrl();
        $this->assertEquals(route('admin.dashboard'), $response->getTargetUrl());
    }

    public function testLoginFail()
    {
        // Called in middleware
        Auth::shouldReceive("guard->check")->once()->andReturn(false);
        // Called in controller
        Auth::shouldReceive("guard->attempt")->once()->andReturn(false);

        /**
         * @var RedirectResponse $response
         */
        $response = $this->post(
            route('admin.login'),
            [
                'email' => 'princess.ardala@example.com',
                'password' => 'draconia',
            ]
        );
        $this->assertEquals(302, $response->status());
        $a = route('admin.dashboard');
        $b = $response->getTargetUrl();
        $this->assertEquals(route('admin.dashboard'), $response->getTargetUrl());
    }

//    public function testLoginTooManyLogins()
//    {
//    }
}
