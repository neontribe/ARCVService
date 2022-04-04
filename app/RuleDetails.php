<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleDetails extends Model
{
  // /**
  //  * The attributes that are mass assignable.
  //  *
  //  * @var array
  //  */
  // protected $fillable = [
  //     'type',
  //     'value',
  // ];

  /**
   * Get the rule this detail is for
   *
   * @return BelongsTo
   */
  public function rule()
  {
      return $this->belongsTo(Rule::class);
  }
}
