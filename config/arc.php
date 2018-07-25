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
];
