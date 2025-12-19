<?php

namespace App\Listeners;

use App\CentreUser;
use Config;
use Illuminate\Auth\Events\Authenticated;
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
            // set the session to the centre default or all the centres they're allowed
            $default = Config::get('arc.default_to_home_centre')
                ? $event->user->homeCentre?->id
                : 'all';

            Session::put('CentreUserCurrentCentreId', $default);
        }
    }
}
