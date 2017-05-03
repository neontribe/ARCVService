<?php

namespace App\Http\Controllers\API;

use Auth;
use Illuminate\Pagination\Paginator;
use Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use App\User;

class VoucherController extends Controller
{

    protected $user;

/*
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('api')->user();
            return $next($request);
        });
    }
*/
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return response()->json($vouchers->toArray());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // TODO: Generalise and reroute
        // for the prupsoes of this iteration, store() will progress "allocated" to "recorded";
        // This would better be generalised as an "update" that progresses to the given state for each voucher.


        // TODO: validation
        // does it declare itself a format ?
        // formats to support
        // application/json <- preferred
        // application/x-www-form-urlencoded <- from a dumb POST form?
        // Rules:
        // - needs a trader id
        // - needs an array of vouchers

        /*
         * expecting a body of type application/josn
        {
            "user_id" : 1,
	        "trader_id" : 1,
	        "vouchers"	: [
                "SOL00000001",
                "SOL00000002",
                "SOL00000002",
                "SOL00000002",
                "SOL00000003",
                "SOL00000014"
                ]
        }

        with current seeds, all should fail except the last one.

        */


        //TODO: and duplicates?
        $responseData = [
            'success' => [],
            'failure' => []
        ];


        $transition = "collect";

        if (request()->isJson()) {
            // get all the data to an assoc array.
            $data = request()->json()->all();
        }

        if (!$user = User::find($data['user_id'])) {
            $user = User::find(1); // just be the first person for now.
        }
        Auth::login($user);


        if (!$trader = Trader::find($data['trader_id'])) {
            return response("no trader data", 400);
        }

        if (!$data['vouchers'] || $data['vouchers'] < 1) {
            return response("no voucher data", 400);
        }

        $inputVouchers = $data['vouchers'];
        natsort($inputVouchers);

        $uniqueVouchers = array_unique($inputVouchers);

        $vouchers = Voucher::findByCodes($uniqueVouchers);

        foreach ($vouchers as $voucher) {
            // can we?
            if ($voucher->transitionAllowed($transition)) {
                // yes! do the thing!
                $voucher->trader_id = $trader->id;

                // this saves the model too.
                $voucher->applyTransition($transition);
                array_push($responseData['success'], $voucher->code);
            } else {
                // no! add it to a list of failures.
                array_push($responseData['failure'], $voucher->code);
            }
        }

        return response()->json($responseData, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json(Voucher::findByCode($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function edit(Voucher $voucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Voucher $voucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function destroy(Voucher $voucher)
    {
        //
    }

    /**
     * show the transitions that a voucher has gone through
     *
     * @param \App\Voucher $voucher
     * @return \Illuminate\Http\Response
     */
    public function showTransitions(Voucher $voucher)
    {
        //
    }

    /**
     * Redeem a bunch of vouchers at once.
     * Look for a complex data structure in the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function batchRedeem(Request $request)
    {
        //
    }

    /**
     * Redeem a single voucher.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function redeem(Request $request, Voucher $voucher)
    {

    }
}
