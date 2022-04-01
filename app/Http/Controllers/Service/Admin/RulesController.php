<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Rules;
use App\Sponsor;
use Illuminate\Http\Request;

class RulesController extends Controller
{
  public function index()
  {
      $rules = Rules::get();
      foreach ($rules as $key => $rule) {
        $desc = Rules::describe($rule);
        \Log::info($desc);
      }
      return view('service.rules', compact('rules'));
  }

  public function create()
  {
      return view('service.rules');
  }
}
