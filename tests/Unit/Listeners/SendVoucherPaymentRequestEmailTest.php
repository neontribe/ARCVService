<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\Listeners\SendVoucherPaymentRequestEmail;
use App\Mail\VoucherPaymentRequestEmail;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use URL;

class SendVoucherPaymentRequestEmailTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    protected $traders;
    protected $vouchers;
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

        // confirm the vouchers.
        $vouchers->each(function ($v) {
            $v->applyTransition('confirm');
            $v->getPriorState()
            ->save();
        });

        $title = 'Test Rose Voucher Payment Request';
        Auth::login($user);
        $file = TraderController::createVoucherListFile($trader, $vouchers, $title, Auth::user()->name);
        $programme_amounts = TraderController::getProgrammeAmounts($vouchers);
        $event = new VoucherPaymentRequested($user, $trader, $vouchers, $file, $programme_amounts);
        $listener = new SendVoucherPaymentRequestEmail();
        $listener->handle($event);


        $viewData = [
            'user' => $user,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'market' => $trader->market,
            'programme_amounts' => $programme_amounts,
        ];

        Mail::assertSent(VoucherPaymentRequestEmail::class, 1);
        Mail::assertSent(VoucherPaymentRequestEmail::class, function (VoucherPaymentRequestEmail $mail) use ($viewData) {
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
            return true;
        });
    }
}
