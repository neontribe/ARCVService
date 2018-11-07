<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Bundle;
use App\Registration;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class HistoryController extends Controller
{
    public function show(Registration $registration)
    {
        $pri_carer = $registration->family->carers->all();
        $bundles = $registration->bundles()->disbursed()->get();

        $periodObject = new \DatePeriod(
            $bundles->last()->disbursed_at->startOfWeek(Carbon::MONDAY),
            CarbonInterval::week(),
            Carbon::now()->startOfWeek(Carbon::MONDAY)
        );

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($pri_carer),
            'bundles' => $bundles,
            'week_commencing' => $periodObject
        ]);
    }
}
