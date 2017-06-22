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
use Log;

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
        // Request date string as dd-mm-yyyy
        $date = $request->submission_date ? $request->submission_date : null;
        $file = $this->createVoucherHistoryFile($trader, $date);

        event(new VoucherHistoryEmailRequested(Auth::user(), $trader, $file));

        return response()->json(['message' => 'Thanks. If you don\'t receive an email with your voucher history, please try again later.'], 202);
    }

    /**
     * Helper to create the Trader's Voucher history file.
     *
     * @param  \App\Trader  $trader
     * @return txt/csv File
     */
    public function createVoucherHistoryFile(Trader $trader, $date = null)
    {
        $vouchers = $trader->vouchersConfirmed;
        $data = [
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
            if ($v->paymentPendedOn) {
                $pended_day = $v->paymentPendedOn->updated_at->format('d-m-Y');
                // Either all the pended vouchers (null date) or the requested one.
                if ($date === null || $date === $pended_day) {
                    $data['vouchers'][] = $v;
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
            $excel->setTitle($data['trader'] . 'Voucher History')
                ->setCompany($data['user'])
                ->setDescription('A report containing voucher history.')
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
