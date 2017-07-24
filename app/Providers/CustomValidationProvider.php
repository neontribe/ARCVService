<?php

namespace App\Providers;

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
