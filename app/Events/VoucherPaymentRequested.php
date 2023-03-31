<?php

namespace App\Events;

use App\User;
use App\Trader;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class VoucherPaymentRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Trader $trader;
    public array $vouchers;
    public string $file;
    public array $programme_amounts;

    /**
     * VoucherPaymentRequested constructor.
     * @param User $user
     * @param Trader $trader
     * @param array $vouchers
     * @param string $file
     * @param array $programme_amounts
     */
    public function __construct(User $user, Trader $trader, array $vouchers, string $file, array $programme_amounts)
    {
        $this->user = $user;
        $this->trader = $trader;
        $this->file = $file;
        $this->vouchers = $vouchers;
        $this->programme_amounts = $programme_amounts;
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
