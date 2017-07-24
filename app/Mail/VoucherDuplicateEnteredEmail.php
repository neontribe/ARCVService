<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VoucherDuplicateEnteredEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $trader;
    public $voucher;
    public $vouchercode;
    public $market;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $voucher)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        $this->voucher = $voucher;
        $this->market = $trader->market;
        $this->vouchercode = $voucher->code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('api.emails.voucher_duplicate_email')
            ->text('api.emails.voucher_duplicate_email_textonly')
        ;
    }
}
