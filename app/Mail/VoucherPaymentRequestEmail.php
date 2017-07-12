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
    public $file;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $trader, $file)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        $this->file = $file;
        $this->vouchers = $trader->vouchers;
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
