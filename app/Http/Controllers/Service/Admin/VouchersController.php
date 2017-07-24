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
        $vouchers = Voucher::orderBy('id', 'desc')->paginate(50);
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

        $sponsor_ids = Sponsor::all()->pluck('id')->toArray();

        $voucher_rules = [
            'sponsor_id' => [
                'required',
                Rule::in($sponsor_ids)
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

        Validator::make($request->all(), $voucher_rules, $messages)->validate();

        $codes = range($request['start'], $request['end']);

        $sponsor = Sponsor::find($request['sponsor_id'])->first();
        $shortcode = $sponsor->shortcode;
        $now_time = Carbon::now();
        foreach ($codes as $c) {
            $v = new Voucher();
            $v->code = $shortcode . $c;
            $v->sponsor_id = $request['sponsor_id'];
            $v->currentstate = 'requested';
            $v->created_at = $now_time;
            $v->updated_at = $now_time;
            $new_vouchers[] = $v->attributesToArray();
        }
        // batch insert.
        // Todo : there's NO "this voucher already exists" checking!!
        Voucher::insert($new_vouchers);

        $vouchers = Voucher::where('created_at', '=', $now_time)
            ->where('updated_at', '=', $now_time)
            ->where('currentstate', '=', 'requested')
            ->get()
        ;

        // batch progress
        foreach ($vouchers as $voucher) {
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            // printed vouchers should now be redeemable.
        }

        $notification_msg = trans('service.messages.vouchers_create_success',[
            'shortcode' => $shortcode,
            'start' => $request['start'],
            'end' => $request['end'],
        ]);
        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notification_msg)
            ;
    }
}
