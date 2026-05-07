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
        return [
            // MUST be present; MUST be in "yes, on, 1, or true"
            'consent' => 'required|accepted',
            // MUST be present; MUST be a not-null string
            'pri_carer' => 'required|string',
            'pri_carer_ethnicity' => [
                Rule::in(array_merge(
                    [0, '0'],
                    array_keys(config('arc.ethnicity_desc'))
                )),
            ],
            'pri_carer_language' => [
                'nullable',
                'not-regex:/^.*[\p{C}].*$/u',
                'regex:/^[A-Za-z.\s\'—-]+$/',
            ],
            // May be nullable, MUST be a standard
            'pri_carer_email' => 'nullable|email:rfc',
            'pri_carer_telno' => 'nullable|phone:GB',
            // MAY be present, Min 1
            'new_carers' => 'array|min:1',
            // MAY be present, fit regexes
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
            'children.*.deferred' => 'boolean',
            'children.*.is_pri_carer' => 'boolean',
            // SOMETIMES is present (SP doesn't have them) MUST be in listed states
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
        ];
    }

    /**
     * Get custom attribute names for validator error messages.
     */
    public function attributes(): array
    {
        return [
            'pri_carer_email' => 'email',
            'pri_carer_telno' => 'telephone number',
        ];
    }
}
