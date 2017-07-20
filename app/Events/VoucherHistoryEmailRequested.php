<?php

namespace App\Events;

use App\User;
use App\Trader;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VoucherHistoryEmailRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $trader;
    public $vouchers;
    public $date;
    public $max_date;
    public $file;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Trader $trader, $vouchers, $file, $date, $max_date = null)
    {
        $this->user = $user;
        $this->trader = $trader;
        $this->date = $date;
        $this->max_date = $max_date;
        $this->vouchers = $vouchers;
        $this->file = $file;
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
