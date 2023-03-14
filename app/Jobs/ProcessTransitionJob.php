<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imtigger\LaravelJobStatus\JobStatus;
use Imtigger\LaravelJobStatus\Trackable;

class ProcessTransitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Trackable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->prepareStatus();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $max = 60;
        $this->setProgressMax($max);

        for ($i = 0; $i <= $max; $i += 1) {
            sleep(1); // Some Long Operations
            $this->setProgressNow($i);
        }



        $tags = [session()->getId()];
        $key = Str::uuid();
        $data = [
            'person' => [
                'name' => 'fred blogs',
                'age' => 'quite old'
            ]
        ];

        Cache::tags($tags)->put($key, $data);
        $this->setOutput(['key' => $key]);
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
            'progress_now',
            'progress_max',
        ]);
        // tell the user where it is
        return response()->json($data, 303, [
            'Location' => route('api.vouchers', ['jobStatus' => $jobStatus->id]),
        ]);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    public static function failed(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    /**
     * @param JobStatus $jobStatus
     * @return JsonResponse
     */
    private static function pollingResponse(JobStatus $jobStatus): JsonResponse
    {
        $data = $jobStatus->only([
            'id',
            'status',
            'progress_now',
            'progress_max',
        ]);
        // tell them to try again in a bit.
        return response()->json($data, 200, [
            //'Location' => route('api.queued-task',['jobStatus' => $jobStatus->id]),
            //'Retry-After' => 20
        ]);
    }
}
