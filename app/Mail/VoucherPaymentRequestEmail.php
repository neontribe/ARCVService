<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VoucherPaymentRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $trader;
    public $vouchers;
    public $file;
    public $market;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $vouchers, $file)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        // Sending vouchers collection in case we want more than just count in email for copy.
        $this->vouchers = $vouchers;
        $this->file = $file;
        $this->market = $trader->market->name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('api.emails.voucher_payrequest_email')
            ->text('api.emails.voucher_payrequest_email_textonly')
            ->attach($this->file['full'], [
                'as' => $this->file['file'],
                'mime' => 'text/csv',
            ]);
    }
}
