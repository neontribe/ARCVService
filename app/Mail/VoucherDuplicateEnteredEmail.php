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
    public $vouchercode;
    public $market;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $vouchercode)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        $this->vouchercode = $vouchercode;
        $this->market = $trader->market;
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
            ]);
    }
}
