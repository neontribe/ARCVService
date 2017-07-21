<?php

namespace App\Events;

use App\User;
use App\Trader;
use App\Voucher;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VoucherDuplicateEntered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $trader;
    public $vouchercode;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Trader $trader, $vouchercode)
    {
        $this->user = $user;
        $this->trader = $trader;
        $this->vouchers->code = $vouchercode;
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
