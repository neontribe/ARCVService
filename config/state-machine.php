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
            'prepared', // used for creating bundles of vouchers
            'allocated', // to families
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
            'bundle' => [
                'from' => ['printed','dispatched'],
                'to' => 'prepared',
            ],
            'disburse' => [
                'from' => ['prepared'],
                'to' => 'allocated',
            ],
            'unbundle-to-printed' => [
                'from' => ['prepared'],
                'to' => 'printed',
            ],
            'unbundle-to-dispatched' => [
                'from' => ['prepared'],
                'to' => 'dispatched',
            ],
            'lose' => [
                'from' => ['dispatched'],
                'to' =>  'lost',
            ],
            'allocate' => [
                'from' => ['dispatched'],
                'to' =>  'allocated',
            ],
            'collect' => [
                'from' => ['printed','dispatched','allocated'],
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
            'reject-to-allocated' => [
                'from' => ['recorded'],
                'to' => 'allocated',
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
                'from' => ['allocated'],
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
