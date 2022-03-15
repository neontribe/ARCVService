<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TraderController extends Controller
{
    /**
     * A list of traders belonging to authenticated user.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // we won't be here if not because of the api middleware but...
        if (!Auth::user()) {
            abort(401);
        }

        // get the enabled traders and some deep relations
        $enabledTraders = Auth::user()
            ->traders()
            ->with(['market.sponsor'])
            ->whereNull('disabled_at')
            ->get();

        // append the featureOverride attribute
        foreach ($enabledTraders as $trader) {
            $sponsor = $trader->market->sponsor;
            if ($sponsor->can_tap === false) {
                $trader->featureOverride = (object)[
                    "pageAccess" => (object)[
                        "tap" => false,
                    ],
                ];
            }
        }

        return response()->json($enabledTraders);
    }

    /**
     * Display the specified resource.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function show(Trader $trader): JsonResponse
    {
        return response()->json($trader);
    }

    /**
     * Display the vouchers associated with the trader.
     * Optionally include query param 'status' to filter results by state.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function showVouchers(Trader $trader): JsonResponse
    {
        // GET api/traders/{trader}/vouchers?status=unpaid
        // Find all vouchers that belong to {trader}
        // that have not had a GIVEN status as a voucher_state IN THEIR LIVES.

        // Could be extended to incorporate ?currentstate=
        // Find all the vouchers that belong to {trader}
        // that have current voucher_state of given currentstate.

        $status = request()->input('status');
        $vouchers = $trader->vouchersWithStatus($status, [
            'code',
            \DB::raw('DATE_FORMAT(updated_at,"%d-%m-%Y") as updated_at')
        ]);
        return response()->json($vouchers);
    }


    /**
     * Display the Trader's Voucher history.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function showVoucherHistory(Trader $trader): JsonResponse
    {
        // get days we pended on as a LengthAwarePaginator data array.
        $pgSubDates = DB::table(static function ($query) use ($trader) {
                $query->selectRaw("SUBSTR(`voucher_states`.`created_at`, 1, 10) as pendedOn")
                    ->from('vouchers')
                    ->leftJoin('voucher_states', 'vouchers.id', 'voucher_states.voucher_id')
                    ->where('voucher_states.to', 'payment_pending')
                    ->where('vouchers.trader_id', $trader->id)
                    ->groupBy('pendedOn')
                    ->orderByDesc('pendedOn');
        }, 'daysFromQuery')
            ->paginate()
            ->toArray();

        // if there are any, make a query
        if ($pgSubDates["total"] === 0) {
            $data = [];
        } else {
            // get the first and last dates from that.
            $toDate = Arr::first($pgSubDates["data"])->pendedOn . ' 23:59:59';
            $fromDate = Arr::last($pgSubDates["data"])->pendedOn . ' 00:00:00';

            // get histories between those dates.
            $histories = self::paymentHistoryBetweenDateTimes($trader, $fromDate, $toDate)->all();

            // process the data into an array
            $data = self::historyGroupByDate($histories);
        }

        $links = implode(', ', [
            '<' . $pgSubDates['path'] . '?page=' . $pgSubDates['current_page'] . '>; rel="current"',
            '<' . $pgSubDates['first_page_url'] . '>; rel="first"',
            '<' . $pgSubDates['prev_page_url'] . '>; rel="prev"',
            '<' . $pgSubDates['next_page_url'] . '>; rel="next"',
            '<' . $pgSubDates['last_page_url'] . '>; rel="last"',
        ]);

        return response()
            ->json(array_values($data), 200, ['Links' => $links]);
    }

    /**
     * Email the Trader's Voucher history.
     *
     * @param Request $request
     * @param Trader $trader
     * @return JsonResponse
     */
    public function emailVoucherHistory(Request $request, Trader $trader): JsonResponse
    {
        $vouchers = $trader->vouchersConfirmed;
        $title = 'A report containing voucher history.';
        // Request date string as dd-mm-yyyy
        $date = $request->submission_date ?: null;
        $file = $this->createVoucherListFile($trader, $vouchers, $title, $date);

        // If all vouchers are requested attempt to get the minimum and maximum dates for the report.
        if (is_null($date)) {
            [$min_date, $max_date] = Voucher::getMinMaxVoucherDates($vouchers);
            event(new VoucherHistoryEmailRequested(Auth::user(), $trader, $file, $min_date, $max_date));
        } else {
            event(new VoucherHistoryEmailRequested(Auth::user(), $trader, $file, $date));
        }

        $response_text = trans('api.messages.email_voucher_history');

        // If a date is provided generate a specific response message.
        if ($date) {
            $response_text = trans(
                'api.messages.email_voucher_history_date',
                [
                    'date' => $date,
                ]
            );
        }

        return response()->json(['message' => $response_text], 202);
    }

    /**
     * Helper to create a list of Trader Vouchers file.
     *
     * @param Trader $trader
     * @param $vouchers
     * @param $title
     * @param null $date
     * @return false|string
     */
    public function createVoucherListFile(Trader $trader, $vouchers, $title, $date = null)
    {
        $data = [
            'report_title' => $title,
            'user' => Auth::user()->name,
            'trader' => $trader->name,
            // This is currently a nullable relation.
            'market' => $trader->market
                ? $trader->market->name
                : 'no associated market',
            'vouchers' => [],
        ];
        foreach ($vouchers as $v) {
            // If this voucher has been pended for payment.
            // Do we want to do something different if this is a request for payment?
            // If pendedOn and not yet
            if ($v->paymentPendedOn) {
                $pended_day = $v->paymentPendedOn->updated_at->format('d-m-Y');
                // Either all the pended vouchers (null date) or the requested one.
                if ($date === null || $date === $pended_day) {
                    $data['vouchers'][] = [
                        'pended_on' => $v->paymentPendedOn->created_at->format('d-m-Y'),
                        'code' => $v->code,
                        'added_on' => $v->updated_at->format('d-m-Y H:i:s'),
                    ];
                }
            }
        }

        $tmp = fopen('php://temp', 'r+');
        foreach ($data['vouchers'] as $voucher) {
            fputcsv(
                $tmp,
                [
                    $voucher['pended_on'],
                    $voucher['code'],
                    $voucher['added_on'],
                ]
            );
        }
        rewind($tmp);
        $csv = stream_get_contents($tmp);
        fclose($tmp);

        return $csv;
    }

    /**
     * returns a collection of the Trader's vouchers with payment pending between two datetimes.
     * @param Trader $trader
     * @param string $fromDate
     * @param string $toDate
     * @return Collection
     */
    public static function paymentHistoryBetweenDateTimes(Trader $trader, string $fromDate, string $toDate) : Collection
    {
         return DB::table('vouchers')
            ->select(['vouchers.code', 'voucher_states.created_at as payment_pending'])
            ->addSelect([
                'recorded' => VoucherState::select('created_at')
                    ->whereColumn('voucher_id', 'vouchers.id')
                    ->where('to', 'recorded')
                    ->orderByDesc('id')
                    ->limit(1),
                'reimbursed' => VoucherState::select('created_at')
                    ->whereColumn('voucher_id', 'vouchers.id')
                    ->where('to', 'reimbursed')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->leftJoin('voucher_states', 'vouchers.id', 'voucher_states.voucher_id')
            ->where('voucher_states.to', 'payment_pending')
            ->where('vouchers.trader_id', $trader->id)
            ->whereBetween('voucher_states.created_at', [$fromDate, $toDate])
            ->orderByDesc('recorded')
            ->get();
    }

    /**
     * Creates a nested array of data from the histories
     * @param array $histories
     * @return array
     */
    public static function historyGroupByDate(array $histories) : array
    {
        $data = [];
        foreach ($histories as $history) {
            // create a record for this voucher
            $voucher = [
                    'code' => $history->code,
                    'recorded_on' => $history->recorded
                        ? Carbon::createFromFormat('Y-m-d H:i:s', $history->recorded)->format('d-m-Y')
                        : '',
                    'reimbursed_on' => $history->reimbursed
                        ? Carbon::createFromFormat('Y-m-d H:i:s', $history->reimbursed)->format('d-m-Y')
                        : '',
                ];

            // work out the d-m-Y it belongs to.
            $pended_on = Carbon::createFromFormat('Y-m-d H:i:s', $history->payment_pending)->format('d-m-Y');

            // if there's not a d-m-Y record to hold it, make one
            if (!isset($data[$pended_on])) {
                $data[$pended_on] = [
                    'pended_on' => $pended_on,
                    'vouchers' => []
                ];
            }
            // append the new record voucher to the d-m-Y place vouchers on the tree.
            $data[$pended_on]['vouchers'][] = $voucher;
        }
        return $data;
    }
}
