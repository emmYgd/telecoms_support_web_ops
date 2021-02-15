<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDownLine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $referral_id;

    public $referred;

    /**
     * Create a new event instance.
     *
     * @param $referral_id
     * @param $referred
     */
    public function __construct($referral_id, $referred)
    {
        $this->referral_id = $referral_id;

        $this->referred = $referred;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
