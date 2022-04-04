<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Rules;
use App\Sponsor;
use Illuminate\Http\Request;
use App\Http\Requests\RuleRequest;

class RulesController extends Controller
{
  public function index()
  {
      $rules = Rules::get();
      foreach ($rules as $key => $rule) {
        $desc = Rules::describe($rule);
      }
      $new_rule_type = false;
      return view('service.rules', compact('rules', 'new_rule_type'));
  }

  public function new(Request $request)
  {
    $new_rule_type = $request->input('new_rule_type');

    $rules = Rules::get();
    foreach ($rules as $key => $rule) {
      $desc = Rules::describe($rule);
    }
    return view('service.rules', compact('rules', 'new_rule_type'));
  }

  public function create(RuleRequest $request)
  {
      $rule = new Rules([
          'sponsor_id' => 1,
          'name' => $request->input('name'),
          'value' => $request->input('num_vouchers'),
      ]);
      if (!$rule->save()) {
          // Oops! Log that
          Log::error('Bad save for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
          // Throw it back to the user
          return redirect()
              ->route('admin.dashboard')
              ->withErrors('Rule creation failed - DB Error.');
      }
      $rules = Rules::get();
      foreach ($rules as $key => $rule) {
        $desc = Rules::describe($rule);
      }
      return view('service.rules', compact('rules'));
  }
}
