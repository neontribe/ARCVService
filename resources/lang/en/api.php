<?php

return [

    'messages' => [
        'email_voucher_history' => 'Thanks. If you don\'t receive an email with your voucher history, please try again later.',
        'email_voucher_history_date' => '[xXx] Thanks, we\'ve received your request for payment history from :date. If you don\'t receive an email with your voucher history, please try again later.',
        'voucher_payment_requested' => '[xXx] Thanks. An email has been sent to let the team at Alexandra Rose that you have requested payment.',
        'voucher_success' => '[xXx] Voucher is valid',
        'batch_voucher_submit' => 'Thanks! Your queue has been successfully submitted. :success_amount vouchers were accepted, :duplicate_amount were duplicates and :invalid_amount were invalid.',
    ],

    'errors' => [
        'invalid_credentials' => 'The user credentials were incorrect.',
        'voucher_invalid' => 'Please enter a valid voucher code.',
        'voucher_own_dupe' => 'You have already submitted voucher code [xXx]',
        'voucher_other_dupe' => 'It looks like this code has been used already, please double check and try again. If you are still unable to add the voucher code, don\'t worry - mark it as "unrecorded", send it in with your other vouchers and you will still be paid when we receive it. [xXx]',
    ]
];
