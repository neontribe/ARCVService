<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $registration = $this->route('registration');
        // Refuse updates to "left" families;
        // This is an extra, specific permission requirement for the update route.
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
            // MUST be present, array, 1 member
            'pri_carer' => "required|array|min:1|max:1",
            // Element MUST be present; MUST be a not-null string
            'pri_carer.*' => 'required|string',
            // MAY be present; MUST be a not-null string
            'sec_carers' => 'array|min:1',
            'sec_carers.*' => 'string',
            // MAY be present; MUST be a not-null string
            'new_carers' => 'array|min:1',
            'new_carers.*' => 'string',
            // MAY be present, Min 1
            'children' => 'array|min:1',
            // MAY be present alone; MUST be present if child verified, MUST be a date format of '2017-07'
            'children.*.dob' => 'required_if:children.*.verified,=,true|date_format:Y-m',
            // MAY be present; MUST be a boolean
            'children.*.verified' => 'boolean',
            // MUST be present; MUST be in listed states
            'eligibility' => [
                    'required',
                    Rule::in(config('arc.reg_eligibilities')),
                ],
        ];

        return $rules;
    }
}
