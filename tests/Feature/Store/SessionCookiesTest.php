<?php

namespace Tests\Feature\Store;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Centre;
use App\CentreUser;

class SessionCookiesTest extends TestCase
{
    use RefreshDatabase;

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

    public function testCookiesOnAuthenticatedUser()
    {
        $centre = factory(Centre::class)->create();
        $centreUser = factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        $response = $this
            ->actingAs($centreUser, 'store')
            ->followingRedirects()
            ->call('post', route('store.login'))
        ;

        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie->getName() !== 'XSRF-TOKEN') {
                $this->assertTrue($cookie->isHttpOnly());
            }
            $this->assertSame('strict', $cookie->getSameSite());
        }
    }
}
