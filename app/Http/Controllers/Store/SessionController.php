<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateSessionRequest;

class SessionController extends Controller
{
    public function update(StoreUpdateSessionRequest $request)
    {
        // Get centreId
        $input = $request->only(['centre']);

        // Set session
        session(['CentreUserCurrentCentreId' => $input['centre']]);
        return redirect()->back();
    }
}