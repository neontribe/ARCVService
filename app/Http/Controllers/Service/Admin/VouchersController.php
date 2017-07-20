<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Voucher;
use Auth;
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
        return view('service.vouchers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($vouchers)
    {
        //$new_vouchers = [];
        //foreach($vouchers as $voucher) {
        //    $v = new Voucher();
        //    $v-> = $voucher->;
        //    $v-> = $voucher->;
        //    $new_vouchers[] = $v->attributesToArray();
        //}
        //Voucher::insert($new_vouchers);
    }
}
