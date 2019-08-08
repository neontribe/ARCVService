<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NotExistsRule;

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
            // MUST be present, not null, string and not used already.
            'voucher_prefix' => [
                'required',
                'string',
                new NotExistsRule('sponsors', 'shortcode'),
            ]
        ];
        return $rules;
    }

    /**
     * Prep input for validation
     */
    public function prepareForValidation()
    {
        if ($this->has('voucher_prefix')) {
            $this->merge(
                // In this system, we're want it uppercase
                ['voucher_prefix' => strtoupper($this->input('voucher_prefix'))]
            );
        }
    }
}
