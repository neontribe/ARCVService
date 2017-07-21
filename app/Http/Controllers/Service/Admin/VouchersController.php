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
use DB;
use Validator;
use Illuminate\Validation\Rule;

class VouchersController extends Controller
{

    /**
     * Display a listing of Vouchers.
     *
     * @return json
     */
    public function index()
    {
        $vouchers = DB::table('vouchers')->orderBy('id', 'desc')->paginate(50);
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

        /*
         * Validation Logic
         * - codes must be numbers < 8 digits
         * - there must be a start
         * - there must be an end
         * - end *must* be >= start
         * x sponsor_id must be an integer
         * x sponsor_id must be valid
         */
        $sponsorIds = \App\Sponsor::all()->pluck('id')->toArray();

        $voucherRules = [
            'sponsor_id' => [
                'required',
                Rule::in($sponsorIds)
            ],
            'start' => 'required|integer|between:1,99999999',
            'end' => [
                'required',
                'integer',
                'between:1,99999999',
                'ge_field:start'
            ],
        ];

        $messages = [
            'in' => 'The :atrribute must be a valid selection',
            'ge_field' => 'The :attribute must be greater than or equal to :field'
        ];

        Validator::make($request->all(), $voucherRules , $messages)->validate();

        $codes = range($request['start'], $request['end']);

        $sponsor = Sponsor::find($request['sponsor_id'])->first();
        $shortcode = $sponsor->shortcode;
        foreach ($codes as $c) {
            $v = new Voucher();
            $v->code = $shortcode . $c;
            $v->sponsor_id = $request['sponsor_id'];
            $v->currentstate = 'requested';
            $v->created_at = Carbon::now();
            $v->updated_at = Carbon::now();
            $new_vouchers[] = $v->attributesToArray();
        }
        Voucher::insert($new_vouchers);

        // Todo progress vouchers to allocated?

        $notificationMsg = 'Created and activated '. $shortcode.$request['start'] .' to '. $shortcode.$request['end'];
        return redirect()->route('admin.vouchers.index')->with('notification', $notificationMsg);
    }
}
