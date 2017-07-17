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

class VoucherPaymentRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $trader;
    public $vouchers;
    public $file;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Trader $trader, Voucher $vouchers, $file)
    {
        $this->user = $user;
        $this->trader = $trader;
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
