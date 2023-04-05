<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Jobs\ProcessTransitionJob;
use App\Trader;
use App\Voucher;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Imtigger\LaravelJobStatus\JobStatus;

class TransitionController extends Controller
{
    use DispatchesJobs;

    /**
     * Fetch a transition job response
     * route POST api/vouchers/transitions/{id}
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public function show(JobStatus $jobStatus): JsonResponse
    {
        // check we have cached answer
        $key = $jobStatus->output['key'] ?? null;
        return ($key && $data = Cache::get($key))
            ? response()->json($data)
            : response()->json(null, 404)
        ;
    }

    /**
     * New transition function, with queues!
     * route POST api/vouchers/transitions
     *
     * @param ApiTransitionVoucherRequest $request
     * @return JsonResponse
     */
    public function store(ApiTransitionVoucherRequest $request): JsonResponse
    {
        // get our trader
        $trader = Trader::findOrFail($request->input('trader_id'));

        //create unique, cleaned vouchers
        $voucherCodes = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        // fetch the transition
        $transition = $request->input('transition');

        // ... start a new job to fetch data
        $job = new ProcessTransitionJob($trader, $voucherCodes, $transition, Auth::user()->id);
        $this->dispatch($job);

        // find the job status to monitor
        $jobStatus = JobStatus::find($job->getJobStatusId());

        // send the client wherever they need to go
        return $job::monitor($jobStatus);
    }
}
