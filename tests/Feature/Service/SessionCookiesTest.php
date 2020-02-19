<?php

namespace Tests\Feature\Service;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SessionCookiesTest extends TestCase
{
    use DatabaseMigrations;

    /**
     *
     * @return void
     * @test
     */
    public function testCookiesOnLogin()
    {
        $response = $this->get(route('admin.login'));
        $cookies = $response->headers->getCookies();
        list($xsrfToken, $arcCookie) = $cookies;
        /**
         * the XSRF-TOKEN cookie in Laravel 5.5 does not come with
         * the httpOnly flag enabled. This is fixed in 5.7 https://github.com/laravel/framework/pull/24726
         * That's why we're exclud
         * isHttpOnly() returns whether the cookie is only transmitted over HTTP
        */
        $this->assertTrue($arcCookie->isHttpOnly());
        $this->assertSame('strict', $arcCookie->getSameSite());
        $this->assertSame('strict', $xsrfToken->getSameSite());
    }
}
