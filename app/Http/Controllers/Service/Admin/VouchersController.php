<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Sponsor;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Log;
use Response;

class VouchersController extends Controller
{

    /**
     * Display a listing of Vouchers.
     *
     * @return json
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return view('service.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating new Vouchers.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // A range of numbers from first to last.
        // Last can be empty. Then only one is created.
        $sponsors = Sponsor::all();
        return view('service.vouchers.create', compact('sponsors'));
    }

    /**
     * Store Voucher range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeBatch(Request $request)
    {
        $new_vouchers = [];
        $codes = range($request['start'], $request['end']);
        $sponsor = Sponsor::find($request['sponsor_id'])->first();
        foreach ($codes as $c) {
            $v = new Voucher();
            $v->code = $sponsor->shortcode . $c;
            $v->sponsor_id = $request['sponsor_id'];
            $v->currentstate = 'requested';
            $v->created_at = Carbon::now();
            $v->updated_at = Carbon::now();
            $new_vouchers[] = $v->attributesToArray();
        }
        Voucher::insert($new_vouchers);

        // Todo progress vouchers to allocated?
    }
}
