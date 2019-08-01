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

        // TODO Use of back() not ideal as it relies on the referrer being genuine.
        // Since the header is part of many pages, the route is hard to define.
        // Maybe use back()->withInput() and/or reject referrer not in our domain.
        return redirect()->back();
    }
}
