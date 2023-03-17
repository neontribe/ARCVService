<?php

namespace App\Jobs;

use App\Services\TransitionProcessor;
use App\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imtigger\LaravelJobStatus\JobStatus;
use Imtigger\LaravelJobStatus\Trackable;
use Log;

class ProcessTransitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    private Trader $trader;
    private array $voucherCodes;
    private string $transition;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Trader $trader, array $voucherCodes, string $transition)
    {
        $this->prepareStatus();
        $this->trader = $trader;
        $this->voucherCodes = $voucherCodes;
        $this->transition = $transition;
    }

    /**
     * Sends the user to an url where they can monitor the job
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function monitor(JobStatus $jobStatus): JsonResponse
    {
        // this is the body data; needs to tell the client what to do
        $data = array_merge(
            [
                'location' => route('api.queued-task.show', ['jobStatus' => $jobStatus->id]),
                'retry-after' => 2,
            ],
            $jobStatus->only(['id', 'status'])
        );

        // tell them to try again in a bit.
        return response()->json($data, 202);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    private static function pollingResponse(JobStatus $jobStatus): JsonResponse
    {
        // tell the client where to look.
        $data = array_merge(
            [
                'location' => route('api.queued-task.show', ['jobStatus' => $jobStatus->id]),
                'retry-after' => 2,
            ],
            $jobStatus->only(['id', 'status'])
        );
        // tell them to try again in a bit.
        return response()->json($data);
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
        $route = route('api.vouchers.transition-response.show', ['jobStatus' => $jobStatus->id]);
        $data = array_merge(['location' => $route], $jobStatus->only(['id', 'status']));
        // tell the user where it is
        return response()->json($data, 303, [
            'Location' => $route,
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
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $processor = new TransitionProcessor($this->trader, $this->transition);

        $processor->handle($this->voucherCodes);

        $responseData = $processor->constructResponseMessage();

        $key = Str::uuid();
        Cache::put($key, $responseData);
        $this->setOutput(['key' => $key]);
    }
}
