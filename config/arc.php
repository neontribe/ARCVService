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
        'too challenging to use vouchers with health conditions',
        'reached time limit on project',
        'unspecified',
    ],

    /*
    |--------------------------------------------------------------------------
    | The numeric value of the school start month
    |--------------------------------------------------------------------------
    */
    'school_month' => env('ARC_SCHOOL_MONTH', 9), // Default to September.
    'scottish_school_month' => env('ARC_SCOTTISH_SCHOOL_MONTH', 8), // Default to August.

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
        'privacy_policy' => 'https://www.alexandrarose.org.uk/arc-childrens-centre-information-sharing-policy',
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
        'collection',
        'individual'
    ],

    /*
    |--------------------------------------------------------------------------
    | List of reasons families can be eligible.
    |--------------------------------------------------------------------------
    */
    'reg_eligibilities_hsbs' => [
        'healthy-start-applying',
        'healthy-start-receiving',
        'healthy-start-receiving-not-eligible-or-rejected'
    ],
    'reg_eligibilities_nrpf' => [
        'yes',
        'no'
    ],

    /*
    |--------------------------------------------------------------------------
    | Voucher created_at date from which API will complain if un-dispatched
    | vouchers are collected by traders
    |--------------------------------------------------------------------------
    */
    'first_delivery_date' => env('ARC_FIRST_DELIVERY_DATE', '2019-09-26'),

    /*
    |--------------------------------------------------------------------------
    | the programme names we are running
    | eg. "standard", "social prescribing"
    |--------------------------------------------------------------------------
     */
    'programmes' => [
        'Standard',
        'Social Prescribing',
    ],

	/*
    |--------------------------------------------------------------------------
    | Demographic fields for primary carer - ethnic background
    |
    |--------------------------------------------------------------------------
     */

    'ethnicity_desc' => [
        'AAFG' => 'Asian/Asian British - Afghani',
        'ABAN' => 'Asian/Asian British - Bangladeshi',
        'ACHN' => 'Asian/Asian British - Chinese',
        'AIND' => 'Asian/Asian British - Indian',
        'APKN' => 'Asian/Asian British - Pakistani',
        'AOTH' => 'Asian/Asian British - Any other Asian background',
        'BAFR' => 'Black/Black British - African',
        'BCRB' => 'Black/Black British - Caribbean',
        'BMOR' => 'Black/Black British - Moroccan',
        'BSOM' => 'Black/Black British - Somalian',
        'BOTH' => 'Black/Black British - Any other Black/British background',
        'MLAM' => 'Mixed - Latin American',
        'MWAS' => 'Mixed - White and Asian',
        'MWBA' => 'Mixed - White and Black African',
        'MWBC' => 'Mixed - White and Black Caribbean',
        'MOTH' => 'Mixed - Any other Mixed/Multiple background',
        'OARA' => 'Other ethnic group - Arab',
        'OSYR' => 'Other ethnic group - Syrian',
        'OTUR' => 'Other ethnic group - Turkish',
        'OOTH' => 'Other ethnic group - Any other ethnic group',
        'WBRI' => 'White - British',
        'WOTH' => 'White - Any other White background',
        'NOBT' => 'Not answered',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default home centre for store dropdown
    | - `true` for home centre or
    | - `false` for "all" centres a user can see
    |--------------------------------------------------------------------------
     */

    'default_to_home_centre' => env('ARC_DEFAULT_TO_HOME_CENTRE', false),

];
