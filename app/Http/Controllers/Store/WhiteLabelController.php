<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhiteLabelController extends Controller
{
  /**
   * Index the Dashboard options
   */
  public function index()
  {
      return view('store.whitelabel');
  }
}
