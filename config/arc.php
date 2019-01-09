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

];
