<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\Listeners\SendVoucherPaymentRequestEmail;
use App\Market;
use App\Sponsor;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spinen\MailAssertions\MailTracking;
use Swift_Message;
use Tests\TestCase;
use URL;

class SendVoucherPaymentRequestEmailTest extends TestCase
{
    use DatabaseMigrations;
    use MailTracking;

    protected $traders;
    protected $vouchers;
    protected $stateToken;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
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
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }
    }

    /**
     * Custom function to assert that the email wasn't addresses to a recipient
     * @param $recipient
     * @param Swift_Message|null $message
     * @return $this
     */
    protected function seeEmailNotToCcBcc($recipient, Swift_Message $message = null)
    {
        $fail_message = "The last email sent was sent to $recipient.";

        // Not in the To
        $this->assertArrayNotHasKey($recipient, (array)$this->getEmail($message)
            ->getTo(), $fail_message);

        // Not in the Cc
        $this->assertArrayNotHasKey($recipient, (array)$this->getEmail($message)
            ->getCc(), $fail_message);

        // Not in the Bcc
        $this->assertArrayNotHasKey($recipient, (array)$this->getEmail($message)
            ->getBcc(), $fail_message);

        return $this;
    }

    /** @test */
    public function testRequestVoucherPayment()
    {
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

        $event = new VoucherPaymentRequested($user, $trader, $stateToken, $vouchers, $file);
        $listener = new SendVoucherPaymentRequestEmail();
        $listener->handle($event);

        // Make the route to check
        $route = URL::route('store.payment-request.show', $stateToken->uuid);

        // We can improve this - but test basic data is correct.
        // uses laravel helper function e() to prevent errors from names with apostrophes
        $this->seeEmailWasSent()
            ->seeEmailTo(config('mail.to_admin.address'))
            ->seeEmailNotToCcBcc($user->email)
            ->seeEmailSubject('Rose Voucher Payment Request')
            ->seeEmailContains('Hi ' . config('mail.to_admin.name'))
            ->seeEmailContains(e($user->name) . ' has just successfully requested payment for')
            ->seeEmailContains($vouchers->count() . ' vouchers')
            ->seeEmailContains(e($trader->name) . ' of')
            // Has button?
            ->seeEmailContains('<a href="' . $route . '" class="button button-blue" target="_blank">Pay Request</a>')
            // Has link?
            ->seeEmailContains('[' . $route . '](' . $route . ')')
        ;
    }
}
