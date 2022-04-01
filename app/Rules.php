<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rules extends Model
{
  public static function describe($rule)
  {
      $type = $rule->type;
      switch ($type) {
        case 'age':
          $desc = "If age is ";
          $desc .= !is_null($rule->max_year) ? "between " : "over ";
          $desc .= $rule->min_year . " year(s) and " . $rule->min_month . " months";
          $desc .= !is_null($rule->max_year) ? " and " . $rule->max_year . " year(s) and " . $rule->max_month . " months" : "";
          $desc .= " award " . $rule->value . " voucher(s).";
          return $desc;
          break;
        case 'family':
          $desc = "If family exists";
          $desc .= " award " . $rule->value . " voucher(s).";
          return $desc;
          break;
        case 'prescription':
          $desc = "If family member has a prescription";
          $desc .= " award " . $rule->value . " voucher(s).";
          return $desc;
        case 'school':
          $desc = "If child is at ";
          $desc .= $rule->is_at_primary_school ? "primary school " : "is at secondary ";
          $desc .= " award " . $rule->value . " voucher(s).";
          return $desc;
          break;

        default:
          return 'hello';
          break;
      }
  }
}
