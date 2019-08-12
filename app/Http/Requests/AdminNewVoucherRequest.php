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
            'sponsor_id' => 'required|exists:sponsors,id',
            // MUST be present, and integer and between 1 and 99999999
            'start' => 'required|integer|between:1,99999999',
            // MUST be present, and integer, greater/equal to start and between 1 and 99999999
            'end' => 'required|integer|between:1,99999999|ge_field:start'
        ];
    }
}
