<?php

namespace App\Rules;

use DB;
use Illuminate\Contracts\Validation\Rule;

class NotExistsRule implements Rule
{
    // Params passed by rule
    protected $parameters;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->parameters = func_get_args();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Get the params, as we need the tableName [0] and the columnName [1]
        $tableName = $this->parameters[0];

        // if the columnName param is empty, use the attribute
        $columnName = (count($this->parameters) === 1)
            ? $attribute
            : $this->parameters[1];

        return DB::table($tableName)
            ->where($columnName, $value)
            ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans("validation.not_exists");
    }
}
