<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use DateTime;
use App\Bundle;
use App\Registration;

class HistoryController extends Controller
{
    public function show(Registration $registration)
    {
        $pri_carer = $registration->family->carers->all();
        // $bundle = $registration->bundles;
        //
        // $date_collected = DateTime::createFromFormat('Y-m-d', $bundle->disbursed_at);

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($pri_carer)
            // 'bundle' => $bundle,
            // 'collected_on' => $date_collected->format('d-m-Y')
        ]);
    }
}
