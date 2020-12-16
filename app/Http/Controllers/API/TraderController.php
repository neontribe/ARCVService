<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

class TraderController extends Controller
{
    // TODO: replace with equivalent eloquent statements
    private static $traderVoucherHistory = <<<EOD
SELECT
    vouchers.code,
    voucher_states.created_at as 'payment_pending',
    (select voucher_states.created_at
     FROM voucher_states
     WHERE voucher_states.`to` = 'recorded'
       and vouchers.id = voucher_id
     order by id desc
     limit 1) AS 'recorded',
    (select voucher_states.created_at
     from voucher_states
     where vouchers.id = voucher_id
       and `to` = 'reimbursed'
     order by id desc
     limit 1) AS 'reimbursed'
FROM
    vouchers left join voucher_states on vouchers.id = voucher_states.voucher_id
WHERE
    voucher_states.`to` = 'payment_pending'
AND
    vouchers.trader_id = ?
ORDER BY payment_pending DESC, recorded DESC
EOD;

    /**
     * A list of traders belonging to auth's user.
     *
     * @return JsonResponse
     */
    public function index()
    {
        // We won't be here if not because of the api middleware but...
        if (!Auth::user()) {
            abort(401);
        }

        $traders = Auth::user()->traders;
        foreach ($traders as $trader) {
            $sponsor = $trader->market->sponsor;
            if ($sponsor->can_tap === false) {
                $trader->featureOverride = (object)[
                    "pageAccess" => (object)[
                        "tap" => false
                    ]
                ];
            }
        }

        return response()->json($traders, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function show(Trader $trader)
    {
        return response()->json($trader, 200);
    }

    /**
     * Display the vouchers associated with the trader.
     * Optionally include query param 'status' to filter results by state.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function showVouchers(Trader $trader)
    {
        // GET api/traders/{trader}/vouchers?status=unpaid
        // Find all vouchers that belong to {trader}
        // that have not had a GIVEN status as a voucher_state IN THIER LIVES.

        // Could be extended to incorporate ?currentstate=
        // Find all the vouchers that belong to {trader}
        // that have current voucher_state of given currentstate.

        $status = request()->input('status');
        $vouchers = $trader->vouchersWithStatus($status);

        // Get date into display format.
        $formatted_vouchers = [];
        foreach ($vouchers as $v) {
            $formatted_vouchers[] = [
                // In fixtures.
                'code' => $v->code,
                'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $v->updated_at)->format('d-m-Y'),
            ];
        }
        return response()->json($formatted_vouchers, 200);
    }

    /**
     * Display the Trader's Voucher history.
     *
     * @param Trader $trader
     * @return JsonResponse
     */
    public function showVoucherHistory(Trader $trader)
    {
        $query = DB::connection()
            ->getPdo()
            ->prepare(self::$traderVoucherHistory);
        $query->execute([$trader->id]);

        $histories = $query->fetchAll(PDO::FETCH_NUM);

        $data = [];

        foreach ($histories as $history) {
            $pended_on = Carbon::createFromFormat('Y-m-d H:i:s', $history[1])->format('d-m-Y');
            $record = [
                'pended_on' => $pended_on,
                'vouchers' => [
                    [
                        'code' => $history[0],
                        'recorded_on' => $history[2]
                            ? Carbon::createFromFormat('Y-m-d H:i:s', $history[2])->format('d-m-Y')
                            : '',
                        'reimbursed_on' => $history[3]
                            ? Carbon::createFromFormat('Y-m-d H:i:s', $history[3])->format('d-m-Y')
                            : '',
                    ],
                ],
            ];

            if (isset($data[$pended_on])) {
                // append the vouchers
                $data[$pended_on]['vouchers'] = array_merge($data[$pended_on]['vouchers'], $record['vouchers']);
            } else {
                // set one
                $data[$pended_on] = $record;
            }
        }
        return response()->json(array_values($data), 200);
    }

    /**
     * Email the Trader's Voucher history.
     *
     * @param Request $request
     * @param Trader $trader
     * @return JsonResponse
     */
    public function emailVoucherHistory(Request $request, Trader $trader)
    {
        $vouchers = $trader->vouchersConfirmed;
        $title = 'A report containing voucher history.';
        // Request date string as dd-mm-yyyy
        $date = $request->submission_date ? $request->submission_date : null;
        $file = $this->createVoucherListFile($trader, $vouchers, $title, $date);

        // If all vouchers are requested attempt to get the minimum and maximum dates for the report.
        if (is_null($date)) {
            list($min_date, $max_date) = Voucher::getMinMaxVoucherDates($vouchers);
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
}
