<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminNewVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Should be route protected.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return  [
            // MUST be present, and exist in table
            'sponsor_id' => 'required|integer|exists:sponsors,id',
            // MUST be present, and an optionally zero padded number string
            'start' =>' required|string|regex:/^[0-9]+$/',
            'end' => 'required|string|regex:/^[0-9]+$/',
            // MUST be present, and integer and between 1 and 99999999
            'start-serial' => 'between:0,99999999',
            // MUST be present, and integer, greater/equal to start and between 1 and 99999999
            'end-serial' => 'between:0,99999999|ge_field:start'
        ];
    }

    // We need to pre-mangle before validating rules
    public function prepareForValidation()
    {
        // create integer versions to be checked
        $input = array_filter(
            $this->all(['start', 'end']),
            'strlen'
        );
        foreach ($input as $key => $value) {
            $input[$key . "-serial"] = intval($value);
        }
        $this->merge($input);
    }

    // Friendlier messages to reference computed fields to normal ones.
    public function messages()
    {
        return  [
            'start-serial.between.numeric' => 'The start must be between :min and :max.',
            'end-serial.between.numeric' => 'The end must be between :min and :max.',
            'end-serial.ge_field' => 'The end must be greater than or equal to start.'
        ];
    }
}
