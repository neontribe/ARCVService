<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CentreUserAuthenticated
{
    /**
     * Handle the event.
     */
    public function handle(Authenticated $event): void
    {
        // only work for centre users
        if ($event->guard !== 'store') {
            return;
        }
        // a fresh login won't have this key
        if (Session::missing('CentreUserCurrentCentreId')) {
            // could be null, so for now we'll pick the first centre, or fallback to null.
            $id = $event->user->homeCentre?->id ?? $event->user->centres()->first()?->id;
            Log::warning("Centre User {$event->user->id} does not have a home centre");
            Session::put('CentreUserCurrentCentreId', $id);
        }
    }
}
