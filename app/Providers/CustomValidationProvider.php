<?php

namespace App\Providers;

use App\Voucher;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CustomValidationProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Custom ARC form validation rules - Incidentally, Laravel 5.5 has a much better system than this...

        /**
         * Is the supplied numeric field greater than the target numeric field
         */
        Validator::extend('ge_field', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            $ref_field = $parameters[0];
            $ref_value = $data[$ref_field];
            // Really simple integer validator.
            return $value >= $ref_value;
        });

        Validator::replacer('ge_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });

        /**
         * Is the supplied voucher code value greater than the the target voucher code value
         */
        Validator::extend('codeGreaterThan', function ($attribute, $value, $parameters, $validator) {
            // Grab the regex matched array
            $code = Voucher::splitShortcodeNumeric($value);

            // Grab the content of the parameter we pass the rule in a roundabout fashion
            $secondCode = array_get(
                $validator->getData(),
                $parameters[0]
            );

            if (!empty($secondCode)) {
                $other = Voucher::splitShortcodeNumeric($secondCode);
                if (is_array($code) && is_array($other) && is_numeric($code["number"]) && is_numeric($other["number"])) {
                    return intval($code["number"]) > intval($other["number"]);
                }
            }
            // Else "Nope"!
            return false;
        });

        Validator::replacer('codeGreaterThan', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':other', $parameters[0], $message);
        });

        /**
         * Is the supplied voucher code value greater than the the target voucher code value
         */
        Validator::extend('codeGreaterOrEqual', function ($attribute, $value, $parameters, $validator) {
            // Grab the regex matched array
            $code = Voucher::splitShortcodeNumeric($value);

            // Grab the content of the parameter we pass the rule in a roundabout fashion
            $secondCode = array_get(
                $validator->getData(),
                $parameters[0]
            );

            if (!empty($secondCode)) {
                $other = Voucher::splitShortcodeNumeric($secondCode);
                if (is_array($code) && is_array($other) && is_numeric($code["number"]) && is_numeric($other["number"])) {
                    return intval($code["number"]) >= intval($other["number"]);
                }
            }
            // Else "Nope"!
            return false;
        });

        Validator::replacer('codeGreaterOrEqual', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':other', $parameters[0], $message);
        });


        /**
         * Is the supplied voucher value the same sponsor as the target value
         */
        Validator::extend('sameSponsor', function ($attribute, $value, $parameters, $validator) {
            // Grab the regex matched array
            $val = Voucher::splitShortcodeNumeric($value);

            // Grab the content of the parameter we pass the rule in a roundabout fashion
            $otherVal = array_get(
                $validator->getData(),
                $parameters[0]
            );

            if (!empty($otherVal)) {
                $other = Voucher::splitShortcodeNumeric($otherVal);
                if (is_array($val) && is_array($other) && is_string($val["shortcode"]) && is_string($other["shortcode"])) {
                    return $val['shortcode'] === $other['shortcode'];
                }
            }
            return false;
        });

        Validator::replacer('sameSponsor', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':other', $parameters[0], $message);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
