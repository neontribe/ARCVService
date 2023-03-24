<?php

namespace App\Listeners;

use App\Events\VoucherPaymentRequested;
use App\Mail\VoucherPaymentRequestEmail;
use File;
use Log;
use Mail;

class SendVoucherPaymentRequestEmail
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
     * @param  VoucherPaymentRequested  $event
     * @return void
     */
    public function handle(VoucherPaymentRequested $event)
    {
        Mail::to(config('mail.to_admin.address'))
            ->send(new VoucherPaymentRequestEmail(
                $event->user,
                $event->trader,
                $event->vouchers,
                $event->file,
                $event->programme_amounts
            ))
        ;
        Log::info($event->file . ' emailed.');
        File::delete($event->file);
        Log::info($event->file . ' deleted.');
    }
}
