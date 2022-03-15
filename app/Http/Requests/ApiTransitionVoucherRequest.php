<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiTransitionVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        /* expecting a body of type application/json; a collect transition looks like
        {
            "trader_id" : 1,
            "transition" : "collect"
            "vouchers" : [
                "SOL00000001",
                "SOL00000002",
                "SOL00000002",
            ]
        }
        */

        return [
            'trader_id' => 'required|exists:traders,id',
            'transition' => 'required|string',
            'vouchers' => 'required|array|min:1',
            'vouchers.*' => 'required|string',
        ];
    }

    /**
     * custom messages for response
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'trader_id.required' => 'No trader id',
            'trader_id.exists' => 'Trader not found',
            'transition.required' => 'No transition',
            'vouchers.required' => 'No voucher data',
            'vouchers.min' => 'No voucher data',
        ];
    }

    /**
     * On failed validation, respond with a 400
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response($validator->errors()->first(), 400));
    }

    /**
     * The data to be validated should be processed as JSON.
     * @return array
     */
    public function validationData(): array
    {
        return json_decode($this->getContent(), true) ?? [];
    }
}
