<?php

namespace App\Http\Requests;

use App\Voucher;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppendBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): true
    {
        // TODO : determine of existing registration route protection is sufficient.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        return [
            'voucher-quantity' => [
                'nullable',
                'integer',
                'between:1,' . config('arc.bundle_max_voucher_append'),
                'required_without:start',
            ],
            'start' => [
                'exclude_if:voucher-quantity,present',
                'required_without:voucher-quantity',
                'string',
                'exists:vouchers,code',
            ],
            'end' => [
                'exclude_if:voucher-quantity,present',
                'nullable',
                'string',
                'exists:vouchers,code',
                'codeGreaterThan:start',
                'sameSponsor:start',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('voucher-quantity')) {
            $this->replace(['voucher-quantity' => (int)$this->input('voucher-quantity')]);
            return;
        }

        $input = array_filter($this->all(['start', 'end']), 'strlen');

        foreach ($input as $key => $value) {
            $clean = Voucher::cleanCodes((array)$value);
            $input[$key] = strtoupper(array_shift($clean));
        }

        $this->replace($input);
    }
}
