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
        // Todo: replace with check that user can make this request rather than wave them through.
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
            'carer' => 'required|string',
            // MAY be present; MUST be a not-null string
            'carers.*' => 'string',
            // MAY be present; MUST be a date format of '2017-07'
            'children.*' => 'date_format:Y-m',
            // MAY be null (if not present) or 0||1
            'fm_chart' => 'nullable|in:0,1',
            // MAY be null (if not present) or 0||1
            'fm_diary' => 'nullable|in:0,1',
            // MAY be null (if not present) or 0||1
            'fm_privacy' => 'nullable|in:0,1',
        ];

        return $rules;
    }
}
