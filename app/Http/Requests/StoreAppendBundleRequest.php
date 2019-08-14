<?php

namespace App\Http\Requests;

use App\Voucher;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppendBundleRequest extends FormRequest
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
            'start' => 'required|string|exists:vouchers,code',
            // MAY be present, nullable, string, code exists, is GT start and same sponsor as start
            'end' => 'nullable|string|exists:vouchers,code|codeGreaterThan:start|sameSponsor:start',
        ];

        return $rules;
    }

    protected function prepareForValidation()
    {
        // get the input and remove null/empty values.
        $input = array_filter(
            $this->all(['start', 'end']),
            'strlen'
        );

        foreach ($input as $key => $value) {
            $clean = Voucher::cleanCodes((array)$value);
            $input[$key] = strtoupper((array_shift($clean)));
        }
        // replace old input with new input
        $this->replace($input);
    }
}
