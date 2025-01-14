<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherDuplicateEntered;
use App\Listeners\SendVoucherDuplicateEmail;
use App\Mail\VoucherDuplicateEnteredEmail;
use App\Market;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendVoucherDuplicateEmailTest extends TestCase
{
    use RefreshDatabase;

    protected $trader;
    protected $voucher;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class)->create();
        $this->user = factory(User::class)->create();
    }

    public function testVoucherDuplicateEmail()
    {
        Mail::fake();

        $trader = factory(Trader::class)->create(['market_id' => factory(Market::class)->create()->id]);
        $user = $this->user;
        $vouchercode = $this->voucher->code;
        $market = $trader->market;
        $title = 'Test Voucher Duplicate Email';

        $viewData = [
            'user' => $user,
            'vouchercode' => $vouchercode,
            'market' => $market,
            'trader' => $trader,
        ];

        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $this->voucher);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        Mail::assertSent(VoucherDuplicateEnteredEmail::class, 1);
        
        // Some of the original tests had to be tweaked, because the email body has things like:
        // {&quot;name&quot;:&quot;Rosamond Conn&quot;,&quot;email&quot;:&quot;
        // making it tricky to check the contents exactly.
        Mail::assertSent(VoucherDuplicateEnteredEmail::class, function (VoucherDuplicateEnteredEmail $mail) use ($viewData) {
            $email = $mail->build();
            $this->assertEquals($mail->to[0]['address'], config('mail.to_admin.address'));
            $body = view($email->view, $viewData)->render();
            $user_name = $viewData['user']->name;
            $this->assertStringContainsString('Voucher Duplicate Entered', $body);
            $this->assertStringContainsString('Hi ' . config('mail.to_admin.name'), $body);
            $this->assertStringContainsString($user_name, $body);
            $this->assertStringContainsString(' has tried to submit voucher', $body);
            $this->assertStringContainsString($viewData['vouchercode'] . ' against', $body);
            $this->assertStringContainsString($viewData['trader']->name, $body);
            $this->assertStringContainsString('however that voucher has already been submitted by another trader.', $body);
            $this->assertStringContainsString(e($viewData['market']->name) , $body);
            return true;
        });
    }

    public function testVoucherDuplicateEmailNoMarket()
    {
        Mail::fake();

        $trader = factory(Trader::class)->create();
        $user = $this->user;
        $vouchercode = $this->voucher->code;
        $title = 'Test Voucher Duplicate Email';

        $viewData = [
            'user' => $user,
            'vouchercode' => $vouchercode,
            'trader' => $trader,
            'market' => 'no associated market',
        ];

        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $this->voucher);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        Mail::assertSent(VoucherDuplicateEnteredEmail::class, 1);
        Mail::assertSent(VoucherDuplicateEnteredEmail::class, function (VoucherDuplicateEnteredEmail $mail) use ($viewData) {
            $email = $mail->build();
            $this->assertEquals($mail->to[0]['address'], config('mail.to_admin.address'));
            $body = view($email->view, $viewData)->render();
            $user_name = $viewData['user']->name;
            $this->assertStringContainsString('Voucher Duplicate Entered', $body);
            $this->assertStringContainsString('Hi ' . config('mail.to_admin.name'), $body);
            $this->assertStringContainsString($user_name, $body);
            $this->assertStringContainsString(' has tried to submit voucher', $body);
            $this->assertStringContainsString($viewData['vouchercode'] . ' against', $body);
            $this->assertStringContainsString($viewData['trader']->name, $body);
            $this->assertStringContainsString('however that voucher has already been submitted by another trader.', $body);
            $this->assertStringContainsString('no associated market' , $body);
            return true;
        });
    }
}
