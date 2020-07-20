<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        // Filter confirmed vouchers by the selected date.
        if($date && !$max_date) {
            $subset_vouchers = $trader->vouchersConfirmed->filter(function($v) use ($date) {
                $v_date = $v->paymentPendedOn->created_at->format('d-m-Y');
                return ($v_date === $date);
            });
            $this->vouchers = $subset_vouchers;
        }

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
            ->attachData(
                $this->file,
                'Rose Voucher History',
                ['mime' => 'text/csv']
            )
        ;
    }
}
