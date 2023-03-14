<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Jobs\ProcessTransitionJob;
use App\Services\TransitionProcessor;
use App\Trader;
use App\Voucher;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Imtigger\LaravelJobStatus\JobStatus;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    use DispatchesJobs;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function runJob (Request $request): JsonResponse
    {
        // check for a job query
        $jobStatus = JobStatus::find((int) $request->input('jobStatus'));
        if ($jobStatus) {
            // check we have some cache
            $key = $jobStatus->output['key'] ?? null;
            if ($key && $data = Cache::get($key)) {
                return response()->json($data);
            };
        }

        // Otherwise, start a new job to fetch data
        $job = new ProcessTransitionJob([]);
        $this->dispatch($job);

        $jobStatus = JobStatus::find($job->getJobStatusId());

        // and send them wherever that should go
        return $job::monitor($jobStatus);
    }

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
        // get our trader
        $trader = Trader::findOrFail($request->input('trader_id'));

        //create unique, cleaned vouchers
        $voucherCodes = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        $processor = new TransitionProcessor($trader, $request->input('transition'));

        $processor->handle($voucherCodes);

        return response()->json(self::constructResponseMessage($processor));
    }

    /**
     * Helper to construct voucher validation response messages.
     * @param TransitionProcessor $processor
     * @return array
     */
    private static function constructResponseMessage(TransitionProcessor $processor): array
    {
        // If there are any confirmed ones respond appropriately.
        if (!empty($processor->vouchers_for_payment)) {
            return ['message' => trans('api.messages.voucher_payment_requested')];
        }

        // If there is only one voucher code being checked.
        $total_submitted = 0;
        $error_type = '';
        $responses = $processor->responses;
        foreach ($responses as $key => $code) {
            $total_submitted += count($code);

            if (count($code) === 1) {
                // We will only use this if there is a total of 1 voucher submitted.
                // So no problem if 2 sets have 1 voucher in them. It is ignored.
                $error_type = $key;
            }
        }
        if ($total_submitted === 1) {
            return match ($error_type) {
                'success_add' => [
                    'message' => trans('api.messages.voucher_success_add'),
                ],
                'success_reject' => [
                    'message' => trans('api.messages.voucher_success_reject'),
                ],
                'own_duplicate' => [
                    'warning' => trans('api.errors.voucher_own_dupe', [
                        'code' => $responses['own_duplicate'][0],
                    ]),
                ],
                'other_duplicate' => [
                    'warning' => trans('api.errors.voucher_other_dupe', [
                        'code' => $responses['other_duplicate'][0],
                    ]),
                ],
                'failed_reject' => [
                    'warning' => trans('api.errors.voucher_failed_reject', [
                        'code' => $responses['failed_reject'][0],
                    ]),
                ],
                'undelivered' => [
                    'warning' => trans('api.errors.voucher_unavailable', [
                        'code' => $responses['undelivered'][0],
                    ]),
                ],
                default => [
                    'error' => trans('api.errors.voucher_unavailable'),
                ],
            };
        }

        // for a complex response
        return [
            'message' => trans('api.messages.batch_voucher_submit', [
                'success_amount' => count($responses['success_add']),
                'duplicate_amount' => count($responses['own_duplicate']) + count($responses['other_duplicate']),
                'invalid_amount' => count($responses['invalid']) + count($responses['undelivered']),
            ]),
        ];
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
