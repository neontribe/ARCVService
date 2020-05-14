<?php

return [

    'messages' => [
        'voucher_success_add' => 'Voucher is valid',
        'voucher_success_reject' => 'Voucher removed',
        'batch_voucher_submit' => 'Thanks! Your queue has been successfully submitted. :success_amount voucher(s) accepted, :duplicate_amount duplicate(s) and :invalid_amount invalid.',
        'email_voucher_history' => 'Thanks. If you don\'t receive an email with your voucher records, please try again later.',
        'email_voucher_history_date' => 'Thanks, we\'ve received your request for payment records from :date. If you don\'t receive an email with your voucher records, please try again later.',
        'voucher_payment_requested' => 'Thanks, your payment request has been accepted. :payment_request_message',
    ],

    'errors' => [
        'invalid_credentials' => 'The user credentials were incorrect.',
        'voucher_invalid' => 'That isn\'t a valid voucher code, please check the number and try again.',
        'voucher_own_dupe' => 'You have already submitted voucher code :code.',
        'voucher_other_dupe' => 'It looks like the code (:code) has been used already, please double check the whole code and try again. If you are still unable to add the voucher code, don\'t worry - mark it as "already used", send it in with your other vouchers and you will still be paid when we receive it.',
        'voucher_failed_reject' => 'I\'m sorry, we couldn\'t undo that for you at this time. Let us know about this code when you send in your vouchers.',
        'voucher_undelivered' => 'Whoops, please check the whole code (:code) and re-enter. If you are still unable to add the voucher code, don\'t worry - mark it as "undelivered", send it in with your other vouchers and you will still be paid when we receive it.'
    ]
];
