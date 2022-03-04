<?php

namespace Tests\Unit\Controllers\Api;

use App\Market;
use App\Voucher;
use App\Trader;
use App\User;
use App\Sponsor;
use App\Http\Controllers\API\TraderController;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TraderControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $traders;
    protected $vouchers;
    protected $user;

    protected $states = [
        [
            'transition' => 'dispatch',
            'from' => 'printed',
            'to' => 'dispatched',
        ],
        [
            'transition' => 'collect',
            'from' => 'dispatched',
            'to' => 'recorded',
        ],
        [
            'transition' => 'confirm',
            'from' => 'recorded',
            'to' => 'payment_pending',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 'printed', 10)->create();
        $this->user = factory(User::class)->create();

        // Set up voucher states.
        Auth::login($this->user);
        foreach ($this->vouchers as $v) {
            $v->applyTransition('dispatch');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }

        // Progress one to pending_payment.
        $this->vouchers[0]->applyTransition('confirm');

        // Progress a couple to reimbursed.
        $this->vouchers[1]->applyTransition('confirm');
        $this->vouchers[1]->applyTransition('payout');
        $this->vouchers[2]->applyTransition('confirm');
        $this->vouchers[2]->applyTransition('payout');

        // A voucher not belonging to trader 1.
        $this->vouchers[9]->trader_id = 2;
        $this->vouchers[9]->save();

        // Todo set some of the pended_at times to yesterday.
    }

    /**
     * Test for the trader list index controller.
     *
     * Asserts that the correct JSON structure is returned along with the correct market data.
     */
    public function testTradersControllerIndex()
    {
        $trader = factory(Trader::class)->create(
            [
                'market_id' => factory(Market::class)->create([
                    'sponsor_id' => factory(Sponsor::class)->create(["can_tap" => false])->id,
                ])->id,
            ]
        );

        $this->user->traders()->sync([$trader->id]);

        $response = $this->actingAs($this->user, 'api')
            ->get(route('api.traders', $trader->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            [
                "id", "name", "pic_url", "market_id", "featureOverride"
            ]
        ]);
        /**
         * TODO: This is not as good as the assertJsonStructure but that requires phpunit 7.5
         * * And the mail assertions won't let us upgrade.  This will need to be revisited in the future.
        */
        $response->assertJsonPath("0.id", 3);
        $response->assertJsonPath("0.market_id", 1);
        $response->assertJsonPath("0.id", 3);
        $response->assertJsonFragment([
            'id' => $trader->market_id,
            'sponsor_id' => $trader->market->sponsor_id,
            'sponsor_shortcode' => $trader->market->sponsor_shortcode,
            'payment_message' => $trader->market->payment_message,
        ]);
    }

    public function testShowVoucherHistoryCompilesListOfPaymentHistory()
    {
        $traderController = new TraderController;
        $data = json_decode(
            $traderController->showVoucherHistory($this->traders[0])->getContent(),
            false
        );

        $today = Carbon::now()->format('d-m-Y');

        // We should have one group of pended_on vouchers x3.
        $this->assertCount(1, $data);
        $this->assertEquals($data[0]->pended_on, $today);
        $this->assertCount(3, $data[0]->vouchers);

        // Check a few values as expected - just for fun.
        $this->assertEquals($this->vouchers[0]->code, $data[0]->vouchers[0]->code);
        $this->assertEquals('', $data[0]->vouchers[0]->reimbursed_on);
        $this->assertEquals($data[0]->vouchers[1]->recorded_on, $today);
        $this->assertEquals($data[0]->vouchers[2]->reimbursed_on, $today);
    }

    public function testItLimitsTheVoucherHistoryTo15()
    {
        $date = Carbon::now()->subMonths(3);

        // create 49 vouchers to bring the total of payment_pending to 50
        factory(Voucher::class, 'printed', 49)->create([
            'trader_id' => $this->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ])->each(function ($v) use (&$date) {
            // add a day to it
            $date->addDay();
            foreach ($this->states as $state) {
                $base = [
                    'voucher_id' => $v->id,
                    'created_at' => $date->addSeconds(10)->format('Y-m-d H:i:s')
                ];
                $attribs = array_merge($base, $state);
                factory(VoucherState::class)->create($attribs);
            }
        });

        // fire up the controller and ask for some things.
        $traderController = new TraderController;
        $response = $traderController->showVoucherHistory($this->traders[0]);
        $data = json_decode(
            $response->getContent(),
            false
        );
        // see there are 15 items in the body
        $this->assertCount(15, $data);
    }

    public function testItPutsPaginationInTheVoucherHistory()
    {
        $date = Carbon::now()->subMonths(3);

        // create 49 vouchers to bring the total of payment_pending to 50
        factory(Voucher::class, 'printed', 49)->create([
            'trader_id' => $this->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ])->each(function ($v) use (&$date) {
            // add a day to it
            $date->addDay();
            foreach ($this->states as $state) {
                $base = [
                    'voucher_id' => $v->id,
                    'created_at' => $date->addSeconds(10)->format('Y-m-d H:i:s')
                ];
                $attribs = array_merge($base, $state);
                factory(VoucherState::class)->create($attribs);
            }
        });

        // fire up the controller and ask for some things.
        $traderController = new TraderController;

        $response = $traderController->showVoucherHistory($this->traders[0]);

        $headers = $response->headers;

        // there is a links header
        $this->assertArrayHasKey('links', $headers->all());
        $links = $this->linkHeaderToArray($headers->get('links'));

        // see the keys are first, prev, next, last
        $this->assertEquals(['current', 'first', 'prev', 'next', 'last'], array_keys($links));

        // first is page 1
        $this->assertEquals(1, $links['first']['page']);
        $this->assertTrue((bool)filter_var($links['first']['link'], FILTER_VALIDATE_URL));

        // there is no previous page
        $this->assertNull($links["prev"]["page"]);
        $this->assertFalse((bool)filter_var($links['prev']['link'], FILTER_VALIDATE_URL));

        // there is a next page
        $this->assertEquals(2, $links['next']['page']);
        $this->assertTrue((bool)filter_var($links['next']['link'], FILTER_VALIDATE_URL));

        // see there are 4 pages, total
        $this->assertEquals(4, $links['last']['page']);
        $this->assertTrue((bool)filter_var($links['last']['link'], FILTER_VALIDATE_URL));
    }

    /**
     * Tests the email all voucher history API response.
     */
    public function testEmailVoucherHistoryAllDates()
    {
        // Sync the user with trader 1.
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => null,
            ])
            ->assertStatus(202)
            ->assertJson([
                'message' => trans('api.messages.email_voucher_history')
            ])
        ;
    }

    /**
     * Tests the email specific date voucher history API response.
     */
    public function testEmailVoucherHistorySpecificDate()
    {
        // Sync the user with trader 1.
        $this->user->traders()->sync([1]);
        // There should be some vouchers pended today.
        $date = Carbon::now()->format('d-m-Y');
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => $date,
            ])
            ->assertStatus(202)
            ->assertJson([
                'message' => trans(
                    'api.messages.email_voucher_history_date',
                    [
                        'date' => $date,
                    ]
                )
            ])
        ;
    }

    /**
     * Tests the voucher history not emailed to user not auth'd for trader.
     */
    public function testEmailVoucherHistoryToNonAuthdUser()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => null,
            ])
            ->assertStatus(403)
        ;
    }

    /**
     * @param string $header
     * @return array
     */
    private function linkHeaderToArray(string $header)
    {
        $values = [];
        foreach (explode(',', $header) as $link)
        {
            $values = array_merge($values, $this->extractLinkData($link));
        }
        return $values;
    }

    /**
     * @param string $linkHeader
     * @return array[]
     */
    private function extractLinkData(string $linkHeader)
    {
        preg_match('/<(.*?(?:(?:\?|\&)page=(\d+).*)?)>.*rel="(.*)"/', $linkHeader, $matches, PREG_UNMATCHED_AS_NULL);
        return [
            $matches[3] => [
                'link' => $matches[1],
                'page' => $matches[2],
            ],
        ];
    }
}
