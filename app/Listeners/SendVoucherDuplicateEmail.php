<?php

namespace App\Listeners;

use App\Events\VoucherDuplicateEntered;
use App\Mail\VoucherDuplicateEnteredEmail;
use Mail;

class SendVoucherDuplicateEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  VoucherDuplicateEntered $event
     * @return void
     */
    public function handle(VoucherDuplicateEntered $event)
    {
        Mail::to(config('mail.to_admin.address'))
            ->send(new VoucherDuplicateEnteredEmail(
                $event->user,
                $event->trader,
                $event->voucher
            ))
        ;
    }
}
