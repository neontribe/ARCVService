<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Request;

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
     * @param null|array $test_alternatives
     * @return array
     */
    public function rules($test_alternatives = null)
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        $rules = [
            // MUST be present, not null and string
            'name' => 'required|string',
            // MUST be present, not null, email
            'email' => 'required|email',
            // MUST be present, not null, integer, id exists in table, not in alternative_centres.
            'worker_centre' => [
                'required',
                'integer',
                'exists:centres,id',
                // Return array, empty or with alternative_centres and test it
                Rule::notIn(self::getAlternatives($test_alternatives))
            ],
            // MAY be present, MUST be integers, distinct, id exists in table
            'alternative_centres.*' => 'integer|distinct|exists:centres,id',
        ];
        return $rules;
    }

    /**
     * Utility function to make the alternative centres into an array.
     * Permits us to intercept when testing.
     * @param null|array $test_alternatives
     * @return string
     */
    private static function getAlternatives($test_alternatives)
    {
        $alternatives = (
            !is_null($test_alternatives) &&
            is_array($test_alternatives)
        )
            ? $test_alternatives
            : Request::input('alternative_centres.*');
        ;
        return $alternatives;
    }
}
