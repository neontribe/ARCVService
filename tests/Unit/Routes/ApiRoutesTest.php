<?php

namespace Tests\Unit\Routes;

use App\Centre;
use App\Delivery;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Voucher;
use App\Trader;
use App\User;
use App\Market;
use App\Sponsor;
use Auth;
use DB;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $trader;
    protected $vouchers;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();
        $this->trader = factory(Trader::class)->create();
        $this->vouchers = factory(Voucher::class, 10)->state('printed')->create();
        $this->user = factory(User::class)->create();

        // Set up password client
        $this->artisan('passport:client', [
            '--no-interaction' => true,
            '--password' => null,
        ]);

        // fetch client for id and secret
        $this->client = DB::table('oauth_clients')
            ->where('password_client', 1)
            ->first()
        ;

        // Override the .env values with the newly created client.
        config([
            'passport.password_client' => (int) $this->client->id,
            'passport.password_client_secret' => $this->client->secret,
        ]);

        // Setup a centre
        $centre = factory(Centre::class)->create();

        // Make a delivery
        $deliveryDate = Carbon::today();
        $delivery = factory(Delivery::class)->create([
            'centre_id' => $centre->id,
            'dispatched_at' => $deliveryDate,
        ]);

        // Set up voucher states.
        Auth::login($this->user);
        foreach ($this->vouchers as $v) {
            $v->delivery_id = $delivery->id;
            $v->applyTransition('dispatch');
        }
        $this->vouchers[1]->trader_id = 1;
        $this->vouchers[1]->applyTransition('collect');
    }

    public function testMustHaveAtLeastOneEnabledTraderToLogin()
    {
        // create 2 traders and add them to the user
        $traders = factory(Trader::class, 2)->create();
        $this->user->traders()->sync($traders);

        // disable one
        $traders->first()->disable();
        $response = $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'secret',
        ]);
        $response->assertJsonStructure(['access_token', 'expires_in', 'refresh_token']);

        // disable the second too
        $traders->last()->disable();
        $response = $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'secret',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'invalid_credentials',
                'message' => 'The user credentials were incorrect.',
            ]);
    }

    public function testGetAccessTokenWithGoodCredentials()
    {
        $traders = factory(Trader::class)->create();
        $this->user->traders()->sync($traders);

        $response = $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'secret',
        ]);
        $response->assertJsonStructure(['access_token', 'expires_in', 'refresh_token']);
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
        $traders = factory(Trader::class)->create();
        $this->user->traders()->sync($traders);

        $response = $this->post(route('api.login'), [
            'username' => $this->user->email,
            'password' => 'notthesecret',
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

    /** REQUIRES AUTH ------------------------------------------------- */
    public function testShowTraderVouchersRoute()
    {
        // This user is not associated with Trader 1.
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', 1))
            ->assertStatus(403)
        ;

        // Associate this user with Trader id 1.
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', 1))
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([0 => ['code', 'updated_at']])
        ;
    }
    public function testVouchersRouteHasEtags()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', $trader))
            ->assertHeader('etag')
        ;
    }

    public function testVouchersRoute304sWithKnownEtag()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);

        // grab an etag route
        $etag = $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', $trader))
            ->headers->get('etag');

        // regrab it with the etag in If-None-Match
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.vouchers', $trader), [], ['If-None-Match' => $etag])
            ->assertStatus(304);
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
        // Get a valid code.
        $code = $this->vouchers[0]->code;
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['message' => trans('api.messages.voucher_success_add')])
        ;
    }

    public function testCollectInvalidVoucherRoute()
    {
        // Make up a bogus code.
        $code = 'BAD88888888';
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['error' => trans('api.errors.voucher_unavailable')])
        ;
    }

    public function testCollectOwnDuplicateVoucherRoute()
    {
        // Get the code already in recorded state.
        $code = $this->vouchers[1]->code;
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson([
                'warning' => trans('api.errors.voucher_own_dupe', [
                    'code' => $code
                ])
            ])
        ;
    }

    public function testCollectOtherDuplicateVoucherRoute()
    {
        // Transfer to trader 2 and get the code already in recorded state.
        $this->vouchers[1]->trader_id = 2;
        $this->vouchers[1]->save();
        $code = $this->vouchers[1]->code;
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson([
                'warning' => trans('api.errors.voucher_other_dupe', [
                    'code' => $code
                ])
            ])
        ;
    }

    public function testCollectUndeliveredVouchersAfterDeliveriesRoute()
    {
        $created_at = Carbon::parse(config('arc.first_delivery_date'))->addDay();
        $this->vouchers[2]->created_at = $created_at;
        $this->vouchers[2]->delivery_id = null;
        $this->vouchers[2]->save();

        $code = $this->vouchers[2]->code;

        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['warning' => trans('api.errors.voucher_unavailable')])
        ;
    }

    public function testCollectUndeliveredVouchersFromBeforeDeliveriesRoute()
    {
        $created_at = Carbon::parse(config('arc.first_delivery_date'))->subDays(1);
        $this->vouchers[2]->created_at = $created_at;
        $this->vouchers[2]->save();
        $code = $this->vouchers[2]->code;

        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['message' => trans('api.messages.voucher_success_add')])
        ;
    }

    public function testRejectToAllocateVoucherRoute()
    {
        // Get a valid code.
        $code = $this->vouchers[0]->code;
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $code,
            ]
        ];
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['message' => trans('api.messages.voucher_success_add')])
        ;

        $payload['transition'] = 'reject';
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(200)
            ->assertJson(['message' => trans('api.messages.voucher_success_reject')])
        ;
    }

    public function testUnauthenticatedDontCollectVoucherRoute()
    {
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $this->vouchers[0]->code,
            ]
        ];
        $this->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }

    public function testCantCollectVoucherOnBehalfOfNotOwnTraderRoute()
    {
        $payload = [
            'transition' => 'collect',
            'trader_id' => 1,
            'vouchers' => [
                $this->vouchers[0]->code,
            ]
        ];
        // Don't sync trader 1 to our user and try to collect.
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.voucher.transition'), $payload)
            ->assertStatus(403)
            // Policy/ Gate still an issue throwing
            // Illuminate\Auth\Access\AuthorizationException rather than json.
        ;
    }

    public function testUserCanSeeOwnTraders()
    {
        $sponsor = factory(Sponsor::class)->create();
        $market = factory(Market::class)->create(["sponsor_id" => $sponsor->id]);
        $traders = factory(Trader::class, 5)->create()->each(
            function ($t) use ($market) {
                $t->market_id = $market->id;
                $t->save();
            }
        );

        $this->user->traders()->sync($traders);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.traders'))
            ->assertJsonStructure([['id', 'name', 'pic_url', 'market_id']])
            ->assertStatus(200)
        ;
    }

    public function testUnauthenticatedUserCannotSeeTraders()
    {
        $this->json('GET', route('api.traders'))
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }

    public function testUserCanSeeOwnTrader()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader', $trader))
            ->assertStatus(200)
        ;
    }

    public function testUserCannotSeeNotOwnTrader()
    {
        $trader = factory(Trader::class)->create();
        // Don't sync this trader to our user.
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader', $trader))
            ->assertStatus(403)
            // Throwing an Illuminate\Auth\Access\AuthorizationException
            // No desired - Json response. Because of can policy default?
            //->assertJson(['error' => 'Unauthorized'])
        ;
    }

    public function testUnauthenticatedUserCannotSeeTrader()
    {
        $this->json('GET', route('api.traders'))
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }

    public function testUserCanSeeOwnTraderVoucherHistory()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.voucher-history', $trader))
            ->assertStatus(200)
        ;
    }

    public function testVoucherHistoryHasEtags()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.voucher-history', $trader))
            ->assertHeader('etag')
        ;
    }

    public function testVoucherHistory304sWithKnownEtag()
    {
        $trader = factory(Trader::class)->create();
        $this->user->traders()->sync([$trader->id]);

        // grab an etag route
        $etag = $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.voucher-history', $trader))
            ->headers->get('etag');

        // regrab it with the etag in If-None-Match
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.voucher-history', $trader), [], ['If-None-Match' => $etag])
            ->assertStatus(304);
    }

    public function testUserCannotSeeAnotherTradersVoucherHistory()
    {
        $trader = factory(Trader::class)->create();
        // Don't sync this trader to our user.
        $this->actingAs($this->user, 'api')
            ->json('GET', route('api.trader.voucher-history', $trader))
            ->assertStatus(403)
            // Throwing an Illuminate\Auth\Access\AuthorizationException
            // No desired - Json response. Because of can policy default?
            //->assertJson(['error' => 'Unauthorized'])
        ;
    }

    public function testUnauthenticatedUserCannotSeeTraderVoucherHistory()
    {
        $this->json('GET', route('api.trader.voucher-history', 1))
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.'])
        ;
    }
}
