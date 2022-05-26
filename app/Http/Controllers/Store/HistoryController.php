<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Registration;
use Auth;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;

class HistoryController extends Controller
{
    public function show(Registration $registration)
    {
        $datesCollection = collect();
        $all_carers = $registration->family->carers->all();
        $disbursedBundlesQuery = $registration->bundles()->disbursed()->orderBy('disbursed_at', 'desc');
        /** @var Collection $disbursedBundles */
        $disbursedBundles = $disbursedBundlesQuery->get();
        $programme = Auth::user()->centre->sponsor->programme;


        if ($disbursedBundles->count() > 0) {
            // Creates a weekly date array from first assigned voucher to today.
            $periodObject = new \DatePeriod(
                $disbursedBundles->last()->disbursed_at->startOfWeek(),
                CarbonInterval::week(),
                Carbon::now()->endOfWeek()
            );

            // Set the weekly date as the key of each item in $datesCollection.
            foreach ($periodObject as $dateTime) {
                // Fetch bundles disbursed between start and end.
                $weeklyCollections = $disbursedBundles
                    ->filter(
                        function ($bundle) use ($dateTime) {
                            return $bundle
                                ->disbursed_at
                                ->between(
                                    $dateTime->copy()->startOfWeek(),
                                    $dateTime->copy()->endOfWeek()
                                )
                            ;
                        }
                    );

                $weeklyCollections->amount = 0;

                foreach ($weeklyCollections as $bundle) {
                    $weeklyCollections->amount += $bundle->vouchers->count();
                };

                // Attach collection of bundles to date
                $datesCollection[$dateTime->startOfWeek()->format('d-m-y')] = $weeklyCollections;
            }

            // Reverse order to have the most recent date first.
            $datesCollection = ($datesCollection)->reverse();
        }

        return view('store.collection_history', [
            'registration' => $registration,
            'pri_carer' => array_shift($all_carers),
            'bundles' => $disbursedBundles,
            'bundles_by_week' => $datesCollection,
            'programme' => $programme,
        ]);
    }
}
