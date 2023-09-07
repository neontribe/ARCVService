<?php

namespace App\Listeners;

use App\Events\VoucherHistoryEmailRequested;
use App\Mail\VoucherHistoryEmail;
use File;
use Log;
use Mail;

class SendVoucherHistoryEmail
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
    public function handle(VoucherHistoryEmailRequested $event)
    {
        Mail::to($event->user)
            ->send(new VoucherHistoryEmail(
                $event->user,
                $event->trader,
                $event->date,
                $event->max_date,
                $event->file
            ))
        ;
        // Log::info(sprintf("Voucher history email sent to %s <%s>", $event->user, $event->trader));
        File::delete($event->file);
        // Log::info(sprintf("Voucher history file deleted for to %s <%s>", $event->user, $event->trader));
    }
}
