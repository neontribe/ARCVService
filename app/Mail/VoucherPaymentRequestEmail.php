<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use URL;

class VoucherPaymentRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $trader;
    public $vouchers;
    public $file;
    public $market;
    public $programme_amounts;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $vouchers, $file, $programme_amounts)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        // Sending vouchers collection in case we want more than just count in email for copy.
        $this->vouchers = $vouchers;
        $this->file = $file;
        $this->programme_amounts = $programme_amounts;
        $this->market = $trader->market ? $trader->market->name : 'no associated market';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('api.emails.voucher_payrequest_email')
            ->subject('Rose Voucher Payment Request')
            ->text('api.emails.voucher_payrequest_email_textonly')
            ->attachData(
                $this->file,
                'Rose Voucher Payment Request',
                ['mime' => 'text/csv']
            );
    }
}
