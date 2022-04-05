<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\RuleDetails;
use App\Rules;
use App\Sponsor;
use Illuminate\Http\Request;
use App\Http\Requests\RuleRequest;

class RulesController extends Controller
{
  /**
   * List of possible rule types
   *
   * @var array
   */
  protected $rule_types = [
    'min_year',
    'min_month',
    'max_year',
    'max_month',
    'pregnancy',
    'family_exists',
    'has_prescription',
    'child_at_school',
  ];

  public function index()
  {
      $rules = Rules::get();
      // foreach ($rules as $key => $rule) {
      //   $desc = Rules::describe($rule);
      // }
      $new_rule_type = false;
      return view('service.rules', compact('rules', 'new_rule_type'));
  }

  public function new(Request $request)
  {
    $new_rule_type = $request->input('new_rule_type');

    $rules = Rules::get();
    // foreach ($rules as $key => $rule) {
    //   $desc = Rules::describe($rule);
    // }
    return view('service.rules', compact('rules', 'new_rule_type'));
  }

  public function create(RuleRequest $request)
  {
      $rule = new Rules([
          'sponsor_id' => 1,
          'name' => $request->input('name'),
          'value' => $request->input('num_vouchers'),
          'type' => $request->input('rule_type'),
          'except_if_age' => $request->input('except_if_age') === 'on' ? 1 : 0,
          'except_if_prescription' => $request->input('except_if_prescription') === 'on' ? 1 : 0,
      ]);
      $rule->save();
      $rule_details_array = $this->sortRuleDetails($request, $rule->id);

      foreach ($rule_details_array as $key => $value) {
        $rule_details = new RuleDetails($value);
        $rule_details->save();
      }

      $rules = Rules::get();
      // foreach ($rules as $key => $rule) {
      //   $desc = Rules::describe($rule);
      // }
      $new_rule_type = false;
      return view('service.rules', compact('rules', 'new_rule_type'));
  }

  public function sortRuleDetails(Request $request, int $rule_id)
  {
    // \Log::info($request);
    $possible_rules = $request->all();
    $rules_to_save = [];
    foreach ($possible_rules as $possible_rule => $value) {
      if ($value === 'on') {
        $value = 1;
      }
      if ($value === 'off') {
        $value = 0;
      }
      if (in_array($possible_rule, $this->rule_types)) {
          array_push($rules_to_save, [
            'type' => $possible_rule,
            'value' => $value,
            'rules_id' => $rule_id,
          ]);
      }
    }

    if (isset($request['has_warning'])) {
      // Something to save the warning.
    }
    return $rules_to_save;
  }
}
