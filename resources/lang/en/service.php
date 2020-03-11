<?php

return [
    'messages' => [
        'vouchers_create' => [
            'success' => 'Created and activated :shortcode :start to :shortcode :end',
        ],
        'vouchers_batchtransiton' => [
            'blocked' => 'The voucher range given contains some vouchers that are not voidable',
            'success' => 'Vouchers :shortcode :start to :shortcode :end have been :transition_to',
        ],
        'vouchers_delivery' => [
            'blocked' => 'The voucher range given contains some vouchers that have already been delivered',
            'success' => 'Delivery to :centre_name created'
        ],
    ],
];
