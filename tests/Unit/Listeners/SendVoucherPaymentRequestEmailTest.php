<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\Listeners\SendVoucherPaymentRequestEmail;
use App\Mail\VoucherPaymentRequestEmail;
use App\Market;
use App\Sponsor;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use URL;

class SendVoucherPaymentRequestEmailTest extends TestCase
{
    use DatabaseMigrations;

    protected $traders;
    protected $vouchers;
    protected $stateToken;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 10)->state('printed')->create();
        $this->user = factory(User::class)->create();

        // Add market to trader[1];
        // We currently require markets to be created with sponsors.
        // TODO In our model is nullable - so noted in Tech Debt.
        $this->traders[1]->market_id = factory(Market::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ])->id;
        $this->traders[1]->save();

        // Set up voucher states.
        Auth::login($this->user);

        // Make a stateToken
        $this->stateToken = factory(StateToken::class)->create();

        foreach ($this->vouchers as $v) {
            $v->applyTransition('dispatch');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }
    }

    /** @test */
    public function testRequestVoucherPayment()
    {
        Mail::fake();

        $user = $this->user;
        $trader = $this->traders[0];
        $vouchers = $trader->vouchers;
        $stateToken = $this->stateToken;

        // confirm the vouchers.
        $vouchers->each(function ($v) use ($stateToken) {
            $v->applyTransition('confirm');
            $v->getPriorState()
            ->stateToken()
            ->associate($stateToken)
            ->save();
        });

        $title = 'Test Rose Voucher Payment Request';
        Auth::login($user);
        $controller = new TraderController();
        $file = $controller->createVoucherListFile($trader, $vouchers, $title);
        $programme_amounts = $controller->getProgrammeAmounts($vouchers);
        $event = new VoucherPaymentRequested($user, $trader, $stateToken, $vouchers, $file, $programme_amounts);
        $listener = new SendVoucherPaymentRequestEmail();
        $listener->handle($event);

        // Make the route to check
        $route = URL::route('store.payment-request.show', $stateToken->uuid);

        $viewData = [
            'user' => $user,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'market' => $trader->market,
            'programme_amounts' => $programme_amounts,
            'actionUrl' => URL::route('store.payment-request.show', $this->stateToken->uuid),
            'actionText' => 'Pay Request'
        ];

        Mail::assertSent(VoucherPaymentRequestEmail::class, 1);
        Mail::assertSent(VoucherPaymentRequestEmail::class, function (VoucherPaymentRequestEmail $mail) use ($viewData, $route) {
            $email = $mail->build();
            $this->assertEquals(config('mail.to_admin.address'), $mail->to[0]['address']);
            $this->assertEquals([], $mail->cc);
            $this->assertEquals([], $mail->bcc);
            $body = view($email->view, $viewData)->render();
            $user_name = $viewData['user']->name;
            $this->assertStringContainsString('Rose Voucher Payment Request', $mail->subject);
            $this->assertStringContainsString('Hi ' . config('mail.to_admin.name'), $body);
            $this->assertStringContainsString(e($user_name), $body);
            $this->assertStringContainsString(' has just successfully requested payment for', $body);
            $this->assertStringContainsString($viewData['vouchers']->count() . ' vouchers', $body);
            $this->assertStringContainsString('for ' .  $viewData['programme_amounts']['numbers']['standard'] . ' standard vouchers', $body);
            $this->assertStringContainsString('and ' .  $viewData['programme_amounts']['numbers']['social_prescription'] . ' social prescription vouchers', $body);
            $this->assertStringContainsString(e($viewData['trader']->name), $body);
            $this->assertStringContainsString('<a href="' . $route . '" class="button button-pink" target="_blank">Pay Request</a>', $body);
            $this->assertStringContainsString('<br>' . $route, $body);
            return true;
        });
    }
}
