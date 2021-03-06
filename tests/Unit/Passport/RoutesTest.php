<?php

namespace Tests\Unit\Passport;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\User;
use DB;

class RoutesTest extends TestCase
{
    use DatabaseMigrations;

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

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_grant',
            'error_description' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
            'hint' => '',
            'message' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
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
            'error' => 'invalid_grant',
            'error_description' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
            'hint' => '',
            'message' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
        ]);
    }
}
