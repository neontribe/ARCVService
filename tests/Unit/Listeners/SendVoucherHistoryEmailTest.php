<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\API\TraderController;
use App\Listeners\SendVoucherHistoryEmail;
use App\Mail\VoucherHistoryEmail;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendVoucherHistoryEmailTest extends TestCase
{
    use DatabaseMigrations;

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

        $this->vouchers[0]->applyTransition('confirm');
        VoucherState::where('voucher_id', $this->vouchers[0]->id)
            ->update(['created_at' => Carbon::tomorrow()]);

        // Progress a couple to reimbursed.
        // For now they display same as pending.
        $this->vouchers[1]->applyTransition('confirm');
        $this->vouchers[1]->applyTransition('payout');
        $this->vouchers[2]->applyTransition('confirm');
        $this->vouchers[2]->applyTransition('payout');


        // A voucher not belonging to trader 1.
        $this->vouchers[9]->trader_id = 2;
        $this->vouchers[9]->save();
    }

    public function testRequestVoucherHistoryEmail()
    {
        Mail::fake();
        
        // Todo this test could be split up and improved.
        $user = $this->user;
        $trader = $this->traders[0];
        $vouchers = $trader->vouchersConfirmed;
        $title = 'Test Rose Voucher Payment Records';

        Auth::login($user);
        $controller = new TraderController();
        $file = $controller->createVoucherListFile($trader, $vouchers, $title);

        list($min_date, $max_date) = Voucher::getMinMaxVoucherDates($vouchers);
        $event = new VoucherHistoryEmailRequested($user, $trader, $file, $min_date, $max_date);
        $listener = new SendVoucherHistoryEmail();
        $listener->handle($event);

        $viewData = [
            'user' => $user,
            'max_date' => $max_date,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'date' => Carbon::now(), // The view needs a date, but it doesn't seem to matter what it is for our purposes
        ];

        Mail::assertSent(VoucherHistoryEmail::class, 1);
        Mail::assertSent(VoucherHistoryEmail::class, function (VoucherHistoryEmail $mail) use ($viewData) {
            $email = $mail->build();
            $this->assertEquals($mail->to[0]['address'], $viewData['user']->email);
            $body = view($email->view, $viewData)->render();
            $user_name = $viewData['user']->name;
            $this->assertStringContainsString('Rose Voucher Payment Records', $mail->subject);
            $this->assertStringContainsString(e($user_name), $body);
            $this->assertStringContainsString('requested a copy of ', $body);
            $this->assertStringContainsString('The file includes payment records from', $body);
            $this->assertStringContainsString($viewData['max_date'], $body);
            return true;
        });
    }
}
