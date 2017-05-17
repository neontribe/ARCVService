<?php

namespace Tests\Unit\Password;

use Tests\TestCase;
use Illuminate\Console\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\User;
use Auth;

class RoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $client;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->artisan('passport:client', [
            '--no-interaction' => true,
            '--password' => null,
        ]);

 // fetch client for id and secret
        $this->client = \DB::table('oauth_clients')->where('password_client', 1)->first();
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

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_credentials',
            'message' => 'The user credentials were incorrect.',
        ]);
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

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_credentials',
            'message' => 'The user credentials were incorrect.',
        ]);
    }

}
