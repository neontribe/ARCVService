<?php

namespace App\Http\Controllers\Store;

use Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateSessionRequest;

class SessionController extends Controller
{
    public function update(StoreUpdateSessionRequest $request)
    {
        Log::info("called update");
        // set session
        return redirect()->back();
    }
}