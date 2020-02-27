<?php

namespace App\Http\Requests;

use App\Http\Requests\AdminNewVoucherRequest;

class AdminUpdateVoucherRequest extends AdminNewVoucherRequest
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
        $additionalRules = [

        ];
        return  array_merge(parent::rules(), $additionalRules);
    }
}
