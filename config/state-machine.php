<?php

return [
    'Voucher' => [
        // class of your domain object
        'class' => App\Voucher::class,

        // property of your object holding the actual state (default is "state")
        'property_path' => 'state',

        // list of all possible states
        'states' => [
            'requested',
            'ordered',
            'printed',
            'dispatched',
            'allocated', // to families
            'recorded',
            'reimbursed',
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
            'allocate' => [
                'from' => ['dispatched'],
                'to' =>  'allocated',
            ],
            'collect' => [
                'from' => ['allocated'],
                'to' =>  'recorded',
            ],
            'payout' => [
                'from' => ['recorded'],
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
