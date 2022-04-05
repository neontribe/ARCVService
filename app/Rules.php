<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rules extends Model
{
  use SoftDeletes;

  protected $dates = ['deleted_at'];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'sponsor_id',
      'name',
      'value',
      'except_if_age',
      'except_if_prescription',
      'type'
  ];

  /**
   * Get the rule_details associated with this rule.
   *
   * @return HasMany
   */
  public function rule_details()
  {
      return $this->hasMany(RuleDetails::class);
  }

  public static function describe($rule)
  {
      $rule_details = $rule->rule_details;
      $type = $rule->type;
      $rule_details_array = [];
      foreach ($rule_details as $index => $rule_detail) {
        $rule_details_array[$rule_detail->type] = $rule_detail->value;
      }
        switch ($type) {
          case 'age':
            return self::getAgeDesc($rule_details_array, $rule->value);
            break;
          case 'family':
            return self::getFamilyDesc($rule_details_array, $rule->value);
            break;
          case 'prescription':
            return self::getPrescriptionDesc($rule_details_array, $rule->value);
            break;
          case 'school':
            return self::getSchoolDesc($rule, $rule->value);
            break;
          default:
            return 'hello';
            break;
        }
    }

    protected static function getAgeDesc($rule_details_array, $value)
    {
        $desc = "If age is ";
        $desc .= isset($rule_details_array['max_year']) ? "between " : "over ";
        $desc .= $rule_details_array['min_year'] . " year(s) and " . $rule_details_array['max_month'] . " months";
        $desc .= isset($rule_details_array['max_year']) ? " and " . $rule_details_array['max_year'] . " year(s) and " . $rule_details_array['max_month'] . " months" : "";
        $desc .= " award " . $value . " voucher(s).";
        return $desc;
    }

    protected static function getFamilyDesc($rule_details_array, $value)
    {
      // \Log::info($rule_details_array);
      $desc = "If family exists";
      if ($rule_details_array['family_exists'] === 'family_exists_total') {
        $desc .= " award " . $value . " voucher(s) for whole family.";
      } else if ($rule_details_array['family_exists'] === 'family_exists_each') {
        $desc .= " award " . $value . " voucher(s) per member.";
      } else {
        // In case another option turns up.
      }
        return $desc;
    }

    protected static function getPrescriptionDesc($rule_details_array, $value)
    {
      $desc = "If family member has a prescription";
      $desc .= " award " . $value . " voucher(s).";
      return $desc;
    }

    protected static function getSchoolDesc($rule, $value)
    {
      $desc = "If child is at ";
      $desc .= $rule->rule_details[0]->value;
      $desc .= " school";
      $desc .= " award 0 vouchers";
      if ($rule->except_if_age) {
        $desc .= ", except if another family member fulfils any of the age rules.";
        $desc .= " In which case, award " .$value . " voucher(s).";
      } else if ($rule->except_if_prescription) {
        $desc .= ", except if another family member fulfils the prescription rule.";
        $desc .= " In which case, award " .$value . " voucher(s).";
      } else {
        $desc .= ".";
      }
      return $desc;
    }
}
