<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateSessionRequest;

class SessionController extends Controller
{
    public function update(StoreUpdateSessionRequest $request)
    {
        // Get centreId
        $input = $request->all(['centre']);

        // Set session
        session(['CentreUserCurrentCentreId' => $input['centre']]);

        // Since the header is part of many pages, the route is hard to define.
        // back() was causing a 403 error when changing centres, it now redirects
        // to search page
        return redirect()->route('store.registration.index');
    }
}
