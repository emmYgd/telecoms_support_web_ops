<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralUpgrade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $uid;

    public $upgrade_id;

    /**
     * Create a new event instance.
     *
     * @param $uid
     * @param $upgrade_id
     */
    public function __construct($uid, $upgrade_id)
    {
        $this->uid = $uid;

        $this->upgrade_id = $upgrade_id;
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
