<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reasons a family might leave
    |--------------------------------------------------------------------------
    */

    'leaving_reasons' => [
        'moving out of centre area',
        'found employment',
        'no longer meets criteria',
        'travel to get/spend vouchers too expensive',
        'disliked market shopping',
        'no attendance',
        'unspecified',
    ],

    /*
    |--------------------------------------------------------------------------
    | The numeric value of the school start month
    |--------------------------------------------------------------------------
    */
    'school_month' => env('ARC_SCHOOL_MONTH', 9), // Default to September.

    /*
    |--------------------------------------------------------------------------
    | Domains for routing
    |--------------------------------------------------------------------------
    */
    'service_domain' => env('ARC_SERVICE_DOMAIN', 'arcv-service.test'),
    'store_domain' => env('ARC_STORE_DOMAIN', 'arcv-store.test'),

    /*
    |--------------------------------------------------------------------------
    | Master Voucher Log
    |--------------------------------------------------------------------------
    */
    'mvl_filename' => env('ARC_MVL_FILENAME', 'MVLReport.zip'),
    'mvl_disk' => env('ARC_MVL_DISK', 'enc'),

    /*
    |--------------------------------------------------------------------------
    | Links
    |--------------------------------------------------------------------------
    */
    'links' => [
        'privacy_policy' => 'https://www.alexandrarose.org.uk/Handlers/Download.ashx?IDMF=e15ec914-aac7-4afe-a9a2-be911ccf1a4e',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum number of vouchers that can be appended to a bundle at once.
    |--------------------------------------------------------------------------
    */
    'bundle_max_voucher_append' => env('ARC_STORE_BUNDLE_MAX_VOUCHER_APPEND', 100),

     /*
    |--------------------------------------------------------------------------
    | Preferences for a centre to print collection sheet.
    |--------------------------------------------------------------------------
    */
    'print_preferences' => [
        'individual',
        'collection'
    ],

];
