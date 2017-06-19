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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $trader, $file)
    {
        $this->user = $user->name;
        $this->trader = $trader->name;
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
            // Todo Add a textonly version.
            //->text('api.emails.voucher_history_email_textonly')
            ->attach($this->file['full'], [
                'as' => $this->file['file'],
                'mime' => 'text/csv',
            ])
        ;
    }
}
