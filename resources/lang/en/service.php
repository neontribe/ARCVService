<?php

return [
    'messages' => [
        'vouchers_create' => [
            'success' => 'Created and activated :shortcode :start to :shortcode :end',
        ],
        'vouchers_batchretiretransition' => [
            'blocked' => 'The voucher range given does not contain any vouchers that are voidable',
            'success' => ':success_codes have been :transition_to. :fail_code_details',
        ],
        'vouchers_delivery' => [
            'blocked' => 'The voucher range given contains some vouchers that have already been delivered',
            'success' => 'Delivery to :centre_name created'
        ],
    ],
];
