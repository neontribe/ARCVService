<?php

namespace App\Listeners;

use App\Events\VoucherHistoryEmailRequested;
use App\Mail\VoucherHistoryEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        Mail::to($event->user)->send(new VoucherHistoryEmail($event->file));
        Log::info($event->file['file'] . ' emailed.');
        File::delete($event->file);
        Log::info($event->file['file'] . ' deleted.');
    }
}
