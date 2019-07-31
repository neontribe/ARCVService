<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminNewCentreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // TODO : determine of existing registration route protection is sufficient.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        $rules = [
            // MUST be present, not null and string
            'name' => 'required|string',
            // MUST be present, not null, email, code exists
            'email' => 'required|email',
            // MUST be present, not null, integer, id exists in table, not in alternative_centres.
            'worker_centre' => [
                'required',
                'integer',
                'exists:centres,id',
                // Return array, empty or with alternative_centres and test it
                Rule::notIn(SELF::joinAlternatives())
            ],
                // MAY be present, MUST be integers, distinct
            'alternative_centres.*' => 'integer|distinct|exists:centres,id',
        ];

        return $rules;
    }

    /**
     * Utility function to make the alternative centres into an array.
     * Gets round an array to string problem the notIn rule has.
     *
     * @return string
     */
    private static function joinAlternatives()
    {
        return '[' . join(',', \Request::all('alternative_centres')) . ']';
    }
}
