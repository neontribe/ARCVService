<?php

namespace App\Http\Controllers\API;

use Auth;
use DB;
use Excel;
use App\Trader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        if (!Auth::user()) { abort(401); }

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

        // What format are we after?
        $datatype = request()->getAcceptableContentTypes()
            ? request()->getAcceptableContentTypes()[0]
            : null
        ;
        switch ($datatype) {
            case 'text/csv':
            case 'application/csv':
                $file = $this->createExcel($trader, $vouchers)
                    ->string('csv');
                return response($file, 200, [
                    'Content-Type' => 'text/csv',
                ]);
            case 'application/xlsx':
                $file = $this->createExcel($trader, $vouchers)
                    ->string('xlsx');
                return response($file, 200, [
                    'Content-Type' => 'application/xlsx',
                ]);
            case 'application/json':
            default:
                return response()->json($vouchers->map(function ($voucher) {
                    $newVoucher = $voucher->toArray();
                    $newVoucher["updated_at"] = $voucher->updated_at->format('d-m-Y H:i.s');
                    return $newVoucher;
                }), 200);
        }
    }

    /**
     * Helper to create Excel downloads.
     * There may be a better place for this but fine for now.
     *
     * @param \App\Trader $trader
     * @param \App\Voucher collection $vouchers
     *
     * @return Maatwebsite\Excel
     */
    private function createExcel($trader, $vouchers)
    {
        $excel = Excel::create('VouchersDownload', function ($excel) use ($trader, $vouchers) {
            // Set the title
            $excel->setTitle($trader->name . 'Voucher Download')
                ->setCompany(Auth::user()->name)
                ->setDescription('A report containing queued vouchers.')
            ;

            $excel->sheet('Vouchers', function ($sheet) use ($trader, $vouchers) {
                $sheet->loadView('api.downloads.vouchers', [
                    'vouchers' => $vouchers,
                    'trader' => $trader->name,
                    'user' => Auth::user()->name,
                ]);
            });
        });

        return $excel;
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

}
