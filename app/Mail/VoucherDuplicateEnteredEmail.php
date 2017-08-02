<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VoucherDuplicateEnteredEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $trader;
    public $vouchers;
    public $market;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $vouchers)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        $this->vouchers = implode(', ', $vouchers);
        $this->market = $trader->market ? $trader->market->name : 'no associated market';
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
