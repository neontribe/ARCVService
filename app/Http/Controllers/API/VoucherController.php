<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Jobs\ProcessTransitionJob;
use App\Voucher;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Imtigger\LaravelJobStatus\JobStatus;

class VoucherController extends Controller
{
    use DispatchesJobs;

    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * route POST api/vouchers
     *
     * @param ApiTransitionVoucherRequest $request
     * @return JsonResponse
     */
    public function transition(ApiTransitionVoucherRequest $request): JsonResponse
    {
        // check for a job query string
        $jobStatus = JobStatus::find((int) $request->input('jobStatus'));
        if ($jobStatus) {
            // check we have cached answer
            $key = $jobStatus->output['key'] ?? null;
            if ($key && $data = Cache::get($key)) {
                // give it back.
                return response()->json($data);
            };
        }

        // Otherwise...
        // ... start a new job to fetch data
        $job = new ProcessTransitionJob($request);
        $this->dispatch($job);

        // find the job status to monitor
        $jobStatus = JobStatus::find($job->getJobStatusId());

        // send the client wherever they need to go
        return $job::monitor($jobStatus);
    }

    /**
     * Display the specified resource.
     *
     * @param string $code
     * @return JsonResponse
     */
    public function show(string $code): JsonResponse
    {
        return response()->json(Voucher::findByCode($code));
    }
}
