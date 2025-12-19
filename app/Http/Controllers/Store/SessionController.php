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
        // redirect to a specific place
        return redirect()->route('store.registration.index');
    }
}
