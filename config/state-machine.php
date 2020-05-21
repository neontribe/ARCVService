<?php

return [
    'Voucher' => [
        // class of your domain object
        'class' => App\Voucher::class,

        // property of your object holding the actual state (default is "state")
        'property_path' => 'currentstate',

        // list of all possible states
        'states' => [
            'printed',
            'dispatched',
            'recorded', // submitted to shortlist
            'payment_pending', // shortlist completed
            'reimbursed', // paid out
            'expired', // if time ran out on stamped batch
            'voided', // when centre loses a batch.
            'retired'
        ],

        // list of all possible transitions
        'transitions' => [

            'dispatch' => [
                'from' => ['printed'],
                'to' => 'dispatched',
            ],
            'void' => [
                'from' => ['dispatched'],
                'to' =>  'voided',
            ],
            'expire' => [
                'from' => ['dispatched'],
                'to' =>  'expired',
            ],
            'collect' => [
                'from' => ['printed','dispatched'],
                'to' =>  'recorded',
            ],
            'reject-to-printed' => [
                'from' => ['recorded'],
                'to' => 'printed',
            ],
            'reject-to-dispatched' => [
                'from' => ['recorded'],
                'to' => 'dispatched',
            ],
            'confirm' => [
                'from' => ['recorded'],
                'to' => 'payment_pending'
            ],
            'payout' => [
                'from' => ['payment_pending'],
                'to' =>  'reimbursed',
            ],
            'retire' => [
                'from' => ['voided','expired'],
                'to' =>  'retired',
            ],
        ],

        // list of all callbacks
        'callbacks' => [

            // will be called when testing a transition
            'guard' => ['guard_on_submitting' => [
                    // call the callback on a specific transition
                    'on' => 'submit_changes',
                    // will call the method of this class
                    'do' => ['MyClass', 'handle'],
                    // arguments for the callback
                    'args' => ['object'],
                ],
            ],

            // will be called before applying a transition
            'before' => [],

            // will be called after applying a transition
            'after' => [],

        ],
    ],
];
