<?php

return [
    'Voucher' => [
        // class of your domain object
        'class' => App\Voucher::class,

        // property of your object holding the actual state (default is "state")
        'property_path' => 'currentstate',

        // list of all possible states
        'states' => [
            'requested',
            'ordered',
            'printed',
            'dispatched',
            'recorded', // submitted to shortlist
            'payment_pending', // shortlist completed
            'reimbursed', // paid out
            'expired',
            'lost',
            'retired'
        ],

        // list of all possible transitions
        'transitions' => [

            'order' => [
                'from' => ['requested'],
                'to' => 'ordered',
            ],
            'print' => [
                'from' =>  ['ordered'],
                'to' => 'printed',
            ],
            'dispatch' => [
                'from' => ['printed'],
                'to' => 'dispatched',
            ],
            'lose' => [
                'from' => ['dispatched'],
                'to' =>  'lost',
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
            'expire' => [
                'from' => ['dispatched', 'printed'],
                'to' =>  'expired',
            ],
            'retire' => [
                'from' => ['lost','expired','reimbursed'],
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
