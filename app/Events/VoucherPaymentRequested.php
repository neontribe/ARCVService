<?php

namespace App\Events;

use App\User;
use App\Trader;
use App\StateToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class VoucherPaymentRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $trader;
    public $stateToken;
    public $vouchers;
    public $file;

    /**
     * VoucherPaymentRequested constructor.
     * @param User $user
     * @param Trader $trader
     * @param StateToken $stateToken
     * @param $vouchers
     * @param $file
     */
    public function __construct(User $user, Trader $trader, StateToken $stateToken, $vouchers, $file)
    {
        $this->user = $user;
        $this->trader = $trader;
        $this->stateToken = $stateToken;
        $this->file = $file;
        $this->vouchers = $vouchers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
