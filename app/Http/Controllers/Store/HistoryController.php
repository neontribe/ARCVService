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
        $datesArray = collect();;
        $all_carers = $registration->family->carers->all();
        $disbursedBundles = $registration->bundles()->disbursed()->orderBy('disbursed_at', 'desc')->get();

        if ($disbursedBundles->count() > 0) {
            // Creates a weekly date array from first assigned voucher to today.
            $periodObject = new \DatePeriod(
                $disbursedBundles->last()->disbursed_at->startOfWeek(),
                CarbonInterval::week(),
                Carbon::now()->endOfWeek()
            );

            // Set the weekly date as the key of each item in $datesArray.
            foreach ($periodObject as $carbonDate) {
                // Get start of week and end of week.
                $startDate = reset($carbonDate);
                $endDate = $carbonDate->endOfWeek();

                $weeklyCollections = [];

                // Fetch bundles disbursed between start and end.
                $weeklyCollections[] = Bundle::getByDates($startDate, $endDate);

                // Attach collection of bundles to date
                $datesArray[$carbonDate->format('d-m-y')] = $weeklyCollections;
            }

            // Reverse order to have the most recent date first.
            // $datesArray = array_reverse($datesArray);
        }

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($all_carers),
            'bundles' => $disbursedBundles,
            'bundles_by_week' => $datesArray,
            'datedBundleArray' => $datesArray
        ]);
    }
}
