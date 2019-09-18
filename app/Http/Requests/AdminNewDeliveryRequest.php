<?php

namespace App\Http\Requests;

use App\Voucher;
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
            'voucher-end' => 'required|string|exists:vouchers,code|codeGreaterThan:voucher-start|sameSponsor:voucher-start',
            // MUST be present, date formatted to Y-m-d, eg 2019-06-21
            'date-sent' => 'required|date_format:Y-m-d',
        ];
    }

    protected function prepareForValidation()
    {
        // get the input and remove null/empty values.
        $input = array_filter(
            $this->all(['voucher-start', 'voucher-end', 'centre', 'date-sent']),
            'strlen'
        );

        foreach ($input as $key => $value) {
            if (in_array($key, ['voucher-start', 'voucher-end'])) {
                $clean = Voucher::cleanCodes((array)$value);
                $input[$key] = strtoupper((array_shift($clean)));
            }
        }
        // replace old input with new input
        $this->replace($input);
    }
}
