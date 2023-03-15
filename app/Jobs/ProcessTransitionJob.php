<?php

namespace App\Jobs;

use App\Services\TransitionProcessor;
use App\Trader;
use App\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imtigger\LaravelJobStatus\JobStatus;
use Imtigger\LaravelJobStatus\Trackable;

class ProcessTransitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public Request $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->prepareStatus();
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // get our trader
        $trader = Trader::findOrFail($this->request->input('trader_id'));

        //create unique, cleaned vouchers
        $voucherCodes = array_unique(Voucher::cleanCodes($this->request->input('vouchers')));

        $processor = new TransitionProcessor($trader, $this->request->input('transition'));

        $processor->handle($voucherCodes);

        $responseData = $processor->constructResponseMessage();

        $key = Str::uuid();
        Cache::put($key, $responseData);
        $this->setOutput(['key' => $key]);
    }

    /**
     * Sends the user to an url where they can monitor the job
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function monitor(JobStatus $jobStatus): JsonResponse
    {
        // this is the body data; needs to tell the client what to do
        $data = $jobStatus->only([
            'id',
            'status',
        ]);
        // tell them to try again in a bit.
        return response()->json($data, 202, [
            'Location' => route('api.queued-task',['jobStatus' => $jobStatus->id]),
            'Retry-After' => 2
        ]);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function queued(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function executing(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function retrying(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function finished(JobStatus $jobStatus): JsonResponse
    {
        // we're done! `303 Other` the user to somewhere they can pick up their data.
        // get the output off the job
        $data = $jobStatus->only([
            'id',
            'status',
        ]);
        // tell the user where it is
        return response()->json($data, 303, [
            'Location' => route('api.voucher.transition', ['jobStatus' => $jobStatus->id]),
        ]);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function failed(JobStatus $jobStatus): JsonResponse
    {
        // TODO think of a better failed handler
        return self::pollingResponse($jobStatus);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    private static function pollingResponse(JobStatus $jobStatus): JsonResponse
    {
        // tell the client where to look.
        $data = $jobStatus->only([
            'id',
            'status',
        ]);
        // tell them to try again in a bit.
        return response()->json($data, 200, [
            'Location' => route('api.queued-task',['jobStatus' => $jobStatus->id]),
            'Retry-After' => 20
        ]);
    }
}
