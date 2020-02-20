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
        $response = $this->get(route('store.login'));
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            /**
            * the XSRF-TOKEN cookie in Laravel 5.5 does not come with
            * the httpOnly flag enabled. This is fixed in 5.7 https://github.com/laravel/framework/pull/24726
            * isHttpOnly() returns whether the cookie is only transmitted over HTTP
            * here we test that only for the cookie we're setting
            */
            if ($cookie->getName() !== 'XSRF-TOKEN') {
                $this->assertTrue($cookie->isHttpOnly());
            }
            $this->assertSame('strict', $cookie->getSameSite());
        }
    }
}
