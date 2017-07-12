<?php

namespace App\Listeners;

use App\Events\VoucherPaymentRequestEmailRequested;
use App\Mail\VoucherPaymentRequestEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * @param  VoucherHistoryEmailRequested  $event
     * @return void
     */
    public function handle(VoucherPaymentRequestEmailRequested $event)
    {
        Mail::to($event->user)
            ->send(new VoucherPaymentRequestEmail(
                $event->user,
                $event->trader,
                $event->file
            ))
        ;
        Log::info($event->file['file'] . ' emailed.');
        File::delete($event->file);
        Log::info($event->file['file'] . ' deleted.');
    }
}
