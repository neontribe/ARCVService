<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Todo: replace with check that user can make this request rather than wave them through.
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
            // MUST be present; MUST be in "yes, on, 1, or true"
            'consent' => 'required|accepted',
            // SOMETIMES is present; MUST be in listed states
            'eligibility-hsbs' => [
                'sometimes',
                'required',
                Rule::in(config('arc.reg_eligibilities_hsbs')),
            ],
            'eligibility-nrpf' => [
                'sometimes',
                'required',
                Rule::in(config('arc.reg_eligibilities_nrpf')),
            ],
            // MUST be present; MUST be a not-null string
            'pri_carer' => 'required|string',
            // MAY be present; MUST be a not-null string
            'new_carers' => 'array|min:1',
            'new_carers.*' => [
                'not-regex:/^.*[\p{C}].*$/u',
                'regex:/^[A-Za-z.\s\'—-]+$/',
            ],
            // MAY be present, Min 1
            'children' => 'array|min:1',
            // MAY be present alone; MUST be present if child verified, MUST be a date format of '2017-07'
            'children.*.dob' => 'required_if:children.*.verified,=,true|date_format:Y-m',
            // MAY be present; MUST be a boolean
            'children.*.verified' => 'boolean',
            'is_pri_carer' => 'boolean'
        ];

        return $rules;
    }
}
