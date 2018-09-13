<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VoucherController extends Controller
{
  public function test()
    {
        return view('store.voucher_allocation');
    }
}

