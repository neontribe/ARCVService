<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class VoucherSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'voucher_code' => ['required_with:search', 'exists:vouchers,code', 'regex:/[A-Z]{2,5}[0-9]{4,8}/']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        return $validator->errors();
    }
}
