<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VoucherHistoryEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $trader;
    public $file;
    public $date;
    public $max_date;
    public $vouchers;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $trader, $date, $max_date, $file)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
        $this->vouchers = $trader->vouchersConfirmed;
        $this->date = $date;
        $this->max_date = $max_date;
        $this->file = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('api.emails.voucher_history_email')
            ->subject('Rose Voucher Payment Records')
            ->text('api.emails.voucher_history_email_textonly')
            ->attach($this->file['full'], [
                'as' => $this->file['file'],
                'mime' => 'text/csv',
            ])
        ;
    }
}
