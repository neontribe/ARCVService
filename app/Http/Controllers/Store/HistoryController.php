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
        $datesArray = [];
        $all_carers = $registration->family->carers->all();
        $disbursedBundles = $registration->bundles()->disbursed()->get();

        if ($disbursedBundles->count() > 0) {
            // Creates a weekly date array from first assigned voucher to today.
            $periodObject = new \DatePeriod(
                $disbursedBundles->last()->disbursed_at->startOfWeek(),
                CarbonInterval::week(),
                Carbon::now()->endOfWeek()
            );

            // Set the weekly date as the key of each item in $datesArray.
            foreach ($periodObject as $carbonDate) {
                $datesArray[$carbonDate->format('d/m/y')] = null;
            }

            $datedBundleArray = $disbursedBundles->mapWithKeys(
                function($bundle) {
                    return [
                        $bundle->disbursed_at->startOfWeek()->format('d/m/y') => $bundle
                    ];
                }
            );

            // Loop through datedBundleArray, if key matches date in
            // $datesArray then assign bundle to it.
            foreach ($datedBundleArray as $week => $bundle) {
                if (array_key_exists($week, $datesArray)) {
                    $datesArray[$week] = $bundle;
                }
            }
        }

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($all_carers),
            'bundles' => $disbursedBundles,
            'bundles_by_week' => $datesArray
        ]);
    }
}
