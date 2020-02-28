<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdminUpdateVoucherRequest extends AdminNewVoucherRequest
{
    /**
     * Get the validation rules that apply to the request.
     * Append ours to the parent ones
     *
     * @return array
     */
    public function rules()
    {
        $additionalRules = [
            // MUST be present AND be in a subset of transitions
            'transition' => [
                'required',
                Rule::in(['expire', 'lose']),
            ],
        ];
        return  array_merge(parent::rules(), $additionalRules);
    }
}
