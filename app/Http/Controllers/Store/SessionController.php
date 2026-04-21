<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateSessionRequest;
use Illuminate\Http\RedirectResponse;

class SessionController extends Controller
{
    public function update(StoreUpdateSessionRequest $request): RedirectResponse
    {
        // Set session
        session(['CentreUserCurrentCentreId' => $request->input('centre')]);

        $previous = url()->previous();
        // if previous is outside the site, route somewhere safe
        if (! str_starts_with($previous, url('/'))) {
            $previous = route('dashboard');
        }

        return redirect()->to($previous);
    }
}
