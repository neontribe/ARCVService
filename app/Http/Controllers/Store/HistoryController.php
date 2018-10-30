<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Bundle;
use App\Registration;

class HistoryController extends Controller
{
    public function show(Registration $registration)
    {
        $pri_carer = $registration->family->carers->all();

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($pri_carer)
        ]);
    }
}
