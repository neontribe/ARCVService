<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Registration;

class StoreUpdateRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Refuse updates to off-scheme users.
        $registration = Registration::find($this->route('registration'))->first();
        return (!isset($registration->family->leaving_on));
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
            // MUST be present; MUST be a not-null string
            'pri_carer.*' => 'required|string',
            // MAY be present; MUST be a not-null string
            'sec_carers.*' => 'string',
            // MAY be present; MUST be a not-null string
            'new_carers.*' => 'string',
            // MAY be present; MUST be a date format of '2017-07'
            'children.*' => 'date_format:Y-m',
        ];

        return $rules;
    }
}
