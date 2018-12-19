<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;

class VoucherController extends Controller
{
    // This belongs here becuase it's largely about arranging vouchers
    public function exportMasterVoucherLog()
    {
        return response('', 200);
    }
}