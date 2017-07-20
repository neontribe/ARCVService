<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\Controller;
use App\Trader;
use Auth;
use Carbon\Carbon;
use DB;
use Excel;
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function edit(Trader $trader)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Trader $trader)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function destroy(Trader $trader)
    {
        //
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
        $vouchers = $trader->vouchersConfirmed;
        $voucher_history = [];
        foreach ($vouchers as $v) {
            // If this voucher has been confirmed.
            if ($v->paymentPendedOn) {
                $pended_day = $v->paymentPendedOn->created_at->format('d-m-Y');
                // Group by the created at date on the payment_pending state.
                $data[$pended_day][] = [
                    'code' => $v->code,
                    'recorded_on' => $v->recordedOn->created_at->format('d-m-Y'),
                    'reimbursed_on' => $v->reimbursedOn
                        ? $v->reimbursedOn->created_at->format('d-m-Y')
                        : ''
                    ,
                ];
                foreach ($data as $pended_day => $vouchers) {
                    $voucher_history[$pended_day] = [
                        'pended_on' => $pended_day,
                        'vouchers' => $vouchers,
                    ];
                }
            }
        }

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
        if(is_null($date)) {
            list($min_date, $max_date) = $this->getMinMaxVoucherDates($vouchers);
            event(new VoucherHistoryEmailRequested(Auth::user(), $trader, $vouchers, $file, $min_date, $max_date));
        } else {
            event(new VoucherHistoryEmailRequested(Auth::user(), $trader, $vouchers, $file, $date));
        }

        $response_text = trans('api.messages.email_voucher_history');

        // If a date is provided generate a specific response message.
        if ($date) {
            $response_text = trans(
                'api.messages.email_voucher_history_date', [
                    'date' => $date
                ]
            );
        }

        return response()->json(['message' => $response_text], 202);
    }

    public function getMinMaxVoucherDates($vouchers) {
        $last = $vouchers[0]->created_at;
        $callback = function($item) use (&$last) {
            // Use the last result if ->paymentPendedOn is not defined.
            $pended_timestamp = $last;

            // Grab the timestamp for min/max check.
            if($item->paymentPendedOn) {
                $pended_timestamp = $item->paymentPendedOn->created_at->timestamp;
                $last = $pended_timestamp;
            }
            return $pended_timestamp;
        };
        // Find the voucher with the earliest created_at date and convert into readable format.
        $min = $vouchers->min($callback);
        $max = $vouchers->max($callback);

        $min_date = Carbon::createFromTimestamp($min)->format('d-m-Y');
        $max_date = Carbon::createFromTimestamp($max)->format('d-m-Y');

        // If max date is the same as min date return null.
        $max_date = ($min_date === $max_date) ? null : $max_date;

        return [$min_date, $max_date];
    }

    /**
     * Helper to create a list of Trader Vouchers file.
     *
     * @param  \App\Trader  $trader
     * @param Collection \App\Voucher $vouchers
     * @param String $report_type
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
        $file = $this->createExcel($data)->store('csv', false, true);
        return $file;
    }

    /**
     * Helper to create Excel and csv files.
     * There may be a better place for this but fine for now.
     *
     * @param Array $data
     *
     * @return Maatwebsite\Excel
     */
    private function createExcel($data)
    {
        $time = Carbon::now()->format('Y-m-d_Hi');
        $filename = str_slug($data['trader'] . '-vouchers-' .$time);
        $excel = Excel::create($filename, function ($excel) use ($data) {
            // Set the title
            $excel->setTitle($data['trader'] . 'Voucher Records')
                ->setCompany($data['user'])
                ->setDescription($data['report_title'])
            ;

            $excel->sheet('Vouchers', function ($sheet) use ($data) {
                $sheet->loadView('api.reports.vouchers', [
                    'user' => $data['user'],
                    'trader' => $data['trader'],
                    'market' => $data['market'],
                    'vouchers' => $data['vouchers'],
                ]);
            });
        });

        return $excel;
    }
}
