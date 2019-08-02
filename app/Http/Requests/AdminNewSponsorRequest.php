<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Request;

class AdminNewSponsorRequest extends FormRequest
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
            // MUST be present, not null, string and not used already.
            'voucher_prefix' => 'required|string',
        ];
        return $rules;
    }
}
