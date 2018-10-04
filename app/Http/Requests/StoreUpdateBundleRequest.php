<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // TODO : determine of existing registration route protection is sufficient.
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
            // MUST be one, cannot be null, cannot be duplicated.
            'vouchers.*' => 'required|distinct|string',

            // Optionally present, if it is, check there's a centre.
            'disbursing_centre_id' => 'numeric:exists:centres',

            // Required with disbursing_centre_id, because
            'bundle_id' => 'integer|required_with:disbursing_centre_id',

            // Optionally present, required if the centre is set
            // TODO : consider before_or_equal:date
            'disbursed_at' => 'required_with:disbursing_centre_id|date_format:Y-m-d'
        ];

        return $rules;
    }
}
