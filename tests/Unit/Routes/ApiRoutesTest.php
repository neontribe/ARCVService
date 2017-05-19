<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\Trader;
use App\User;
use Auth;

class ApiRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $trader;
    protected $vouchers;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->trader = factory(Trader::class)->create();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
        $this->user = factory(User::class)->create();

        // Set up password client
        $this->artisan('passport:client', [
            '--no-interaction' => true,
            '--password' => null,
        ]);

        // fetch client for id and secret
        $this->client = \DB::table('oauth_clients')
            ->where('password_client', 1)
            ->first()
        ;

        // Override the .env values with the newly created client.
        config([
            'passport.password_client' => (int) $this->client->id,
            'passport.password_client_secret' => $this->client->secret,
        ]);

        // Set up voucher states.
        Auth::login($this->user);
        foreach ($this->vouchers as $v) {
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->applyTransition('allocate');
        }
        $this->vouchers[1]->trader_id = 1;
        $this->vouchers[1]->applyTransition('collect');
    }

    public function testGetAccessTokenWithGoodCredentials()
    {
        $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'secret',
        ])->assertJsonStructure(['access_token', 'expires_in', 'refresh_token']);
    }

    public function testDontGetAccessTokenWithBadUsername()
    {
        $this->post(route('api.login'), [
            'username' => 'nottheusersname@example.com',
            'password' => 'secret',
        ])->assertStatus(401)
        ->assertJson([
            'error' => 'invalid_credentials',
            'message' => 'The user credentials were incorrect.',
        ]);
    }

    public function testDontGetAccessTokenWithBadUserPassword()
    {
        $response = $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'notthesecret',
        ])->getContent();

        $this->assertEquals(json_decode($response, true), [
            'error' => 'invalid_credentials',
            'message' => 'The user credentials were incorrect.',
        ]);
    }

    /** REQUIRES AUTH ------------------------------------------------- */

    public function testShowTraderVouchersRoute()
    {
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', 1))
            ->assertJsonStructure([ 0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]])
        ;
    }

    public function testUnauthenticatedDontShowTraderVouchersRoute()
    {
        $this->json('GET', route('api.trader.vouchers', 1))
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }

    public function testCollectVoucherRoute()
    {
        $payload= [
            'user_id' => 1,
            'trader_id' => 1,
            'vouchers' => [
                'RVP12345563',
            ]
        ];
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.collect'), $payload)
            ->assertJsonStructure([
                'success', 'fail', 'invalid'
            ])
        ;
    }

    public function testUnauthenticatedDontCollectVoucherRoute()
    {
        $payload= [
            'user_id' => 1,
            'trader_id' => 1,
            'vouchers' => [
                'RVP12345563',
            ]
        ];
        $this->json('POST', route('api.voucher.collect'), $payload)
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }

    public function testUserCanSeeOwnTrader()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);
        $this->actingAs($this->user, 'api')
            ->get(route('api.traders.trader', $trader->id))
            ->assertStatus(200);
    }

}
