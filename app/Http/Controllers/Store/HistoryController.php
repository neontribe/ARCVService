<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Bundle;
use App\Registration;

class HistoryController extends Controller
{
    /**
     * Index the registration's history
     */
    public function show(Registration $registration)
    {
        return view('store.collection_history', ['registration' => $registration]);
    }
}
