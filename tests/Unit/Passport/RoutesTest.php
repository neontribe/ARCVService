<?php

namespace Tests\Unit\Passport;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use DB;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();

        $this->user = factory(User::class)->create();
        $this->artisan('passport:client', [
            '--no-interaction' => true,
            '--password' => null,
        ]);

        // fetch client for id and secret
        $this->client = DB::table('oauth_clients')
            ->where('password_client', 1)
            ->first()
        ;
    }

    public function testGetAccessTokenWithGoodCredentials()
    {
        $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'username' => $this->user->email,
            'password' => 'secret',
            'scope' => '',
        ])->assertJsonStructure(['access_token', 'refresh_token']);
    }

    public function testDontGetAccessTokenWithBadClientId()
    {
        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->id + 1,
            'client_secret' => $this->client->secret,
            'username' => $this->user->email,
            'password' => 'secret',
            'scope' => '',
        ])->getContent();

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
            'message' => 'Client authentication failed',
        ]);
    }

    public function testDontGetAccessTokenWithBadClientSecret()
    {
        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => 'Notthesecretwearelookingfor',
            'username' => $this->user->email,
            'password' => 'secret',
            'scope' => '',
        ])->getContent();

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
            'message' => 'Client authentication failed',
        ]);
    }

    public function testDontGetAccessTokenWithBadUsername()
    {
        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'username' => 'nottheusersname',
            'password' => 'secret',
            'scope' => '',
        ])->getContent();

        $this->assertEquals(
            [
                'error' => 'invalid_grant',
                'error_description' => 'The user credentials were incorrect.',
                'message' => 'The user credentials were incorrect.',
            ],
            json_decode($response, true)
        );
    }

    public function testDontGetAccessTokenWithBadUserPassword()
    {
        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'username' => $this->user->email,
            'password' => 'notthesecret',
            'scope' => '',
        ])->getContent();

        $this->assertEquals(
            [
                'error' => 'invalid_grant',
                'error_description' => 'The user credentials were incorrect.',
                'message' => 'The user credentials were incorrect.',
            ],
            json_decode($response, true)
        );
    }
}
