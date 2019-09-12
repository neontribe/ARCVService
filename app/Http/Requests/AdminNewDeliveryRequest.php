<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminNewDeliveryRequest extends FormRequest
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
            'centre' => 'required|exists:centres,id',
            // MUST be present, string, exists
            'voucher-start' => 'required|string|exists:vouchers,code',
            // MUST be present, string, exists, greater/equal to start and same sponsor as start
            'voucher-end' => 'required|string|exists:vouchers,code|codeGreaterThan:start|sameSponsor:start',
            // MUST be present, date formatted to Y-m-d, eg 2019-06-21
            'date-sent' => 'required|date_format:Y-m-d',
        ];
    }
}
