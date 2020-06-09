<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use Auth;
use Illuminate\Http\Request;

class TraderController extends Controller
{
    /**
     * A list of traders belonging to auth's user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // We won't be here if not because of the api middleware but...
        if (!Auth::user()) {
            abort(401);
        }

        $traders = Auth::user()->traders;

        return response()->json($traders, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function show(Trader $trader)
    {
        return response()->json($trader, 200);
    }

    /**
     * Display the vouchers associated with the trader.
     * Optionally include query param 'status' to filter results by state.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
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
                'updated_at' => $v->updated_at->format('d-m-Y'),
            ];
        }
        return response()->json($formatted_vouchers, 200);
    }

    /**
     * Display the Trader's Voucher history.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function showVoucherHistory(Trader $trader)
    {
        // TODO: this is still a stopgap; find a way to subselect/pivot in one go not per voucher at the DB layer.
        $vouchers = $trader->vouchersConfirmed;

        $data = [];
        $vouchers->each(function ($v) use (&$data) {
            $history = $v->history()->pluck('created_at', 'to')->toArray();
            if (array_key_exists('payment_pending', $history)) {
                $pended_day = $history['payment_pending']->format('d-m-Y');

                $data[$pended_day][] = [
                    'code' => $v->code,
                    'recorded_on' => (array_key_exists('recorded', $history))
                        ? $history["recorded"]->format('d-m-Y')
                        : '',
                    'reimbursed_on' => (array_key_exists('reimbursed', $history))
                        ? $history["reimbursed"]->format('d-m-Y')
                        : ''
                ];
            }
        });

        $voucher_history = [];

        foreach ($data as $pended_day => $vs) {
            // TODO : is the client using this ordering? We could avoid the uksort by using ISO dates from Carbon
            $voucher_history[$pended_day] = [
                'pended_on' => $pended_day,
                'vouchers' => $vs,
            ];
        }

        uksort($voucher_history, function ($a, $b) {
            return strtotime($b) - strtotime($a);
        });

        return response()->json(array_values($voucher_history), 200);
    }

    /**
     * Email the Trader's Voucher history.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
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
                    'date' => $date
                ]
            );
        }

        return response()->json(['message' => $response_text], 202);
    }

    /**
     * Helper to create a list of Trader Vouchers file.
     *
     * @param \App\Trader $trader
     * @param $vouchers
     * @param $title
     * @param null $date
     * @return txt/csv File
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
                    $voucher['added_on']
                ]
            );
        }
        rewind($tmp);
        $csv = stream_get_contents($tmp);
        fclose($tmp);

        return $csv;
    }
}
