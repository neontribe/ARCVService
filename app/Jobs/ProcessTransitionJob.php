<?php

namespace App\Jobs;

use App\Services\TransitionProcessor\TransitionProcessor;
use App\Trader;
use App\User;
use App\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imtigger\LaravelJobStatus\JobStatus;
use Imtigger\LaravelJobStatus\Trackable;
use Log;

class ProcessTransitionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Trackable;

    private Trader $trader;
    private array $voucherCodes;
    private string $transition;
    private int $runAsId;

    // die to failed_jobs after single failure
    public int $tries = 1;

    // jobs really should not take 600 seconds
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(Trader $trader, array $voucherCodes, string $transition, int $runAsId)
    {
        $this->prepareStatus();
        $this->trader = $trader;
        $this->voucherCodes = $voucherCodes;
        $this->transition = $transition;
        $this->runAsId = $runAsId;
    }

    /**
     * Sends the user to a URL where they can monitor the job.
     */
    public static function monitor(JobStatus $jobStatus): JsonResponse
    {
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

    private static function pollingResponse(JobStatus $jobStatus): JsonResponse
    {
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

    public static function queuedHandler(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    public static function executingHandler(JobStatus $jobStatus): JsonResponse
    {
        return self::pollingResponse($jobStatus);
    }

    public static function retryingHandler(JobStatus $jobStatus): JsonResponse
    {
        // this probably won't happen, but for safety's sake we'll catch it.
        return self::pollingResponse($jobStatus);
    }

    public static function finishedHandler(JobStatus $jobStatus): JsonResponse
    {
        // we're done! should be `303 Other` the user to somewhere they can pick up their data.
        // get the output off the job.
        // iOS is rubbish though - it doesn't treat auth headers right with 303s, so we're relying
        // on the client to manually read the "finished" status :-(
        $route = route('api.vouchers.transition-response.show', ['jobStatus' => $jobStatus->id]);
        $data = array_merge(['location' => $route], $jobStatus->only(['id', 'status']));
        // tell the user where it is
        return response()->json($data, 202, [
            'Location' => $route,
        ]);
    }

    public static function failedHandler(JobStatus $jobStatus): JsonResponse
    {
        // TODO think of a better failed handler
        return self::pollingResponse($jobStatus);
    }

    /**
     * Execute the job.
     *
     * $voucherCodes is kept as an array on the job property because a Builder
     * cannot be serialised to the queue. The Builder is constructed here inside
     * handle(), matching the controller pattern exactly.
     */
    public function handle(): void
    {
        Auth::logout();

        // Login if we're not
        if (Auth::check()) {
            $loginMessage = "This session already has a user [%s]";
        } else {
            Auth::login(User::find($this->runAsId));
            $loginMessage = "This session logged in [%s]";
        }
        $id = Auth::user()->id;
        Log::info(sprintf($loginMessage, $id));

        if ($id === $this->runAsId) {
            $query = Voucher::whereIn('code', $this->voucherCodes);
            $foundCodes = $query->pluck('code')->all();
            $invalidCodes = array_values(array_diff($this->voucherCodes, $foundCodes));

            $processor = new TransitionProcessor($this->trader, $this->transition);

            $response = $processor->handle($query);
            $response->addInvalid($invalidCodes);

            $key = Str::uuid();
            Cache::put($key, $response->constructResponseMessage());
            $this->setOutput(['key' => $key]);
            Auth::logout();
        } else {
            Log::error(sprintf("Incorrect user [%s] for transition job expecting [%d]", $id, $this->runAsId));
        }
    }
}
