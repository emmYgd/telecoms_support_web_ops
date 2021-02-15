<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionEvents
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $service_type;

    public $channel_name;

    public $channel_code;

    public $data;

    /**
     * Create a new event instance.
     *
     * @param $service_type
     * @param $channel_name
     * @param $channel_code
     * @param array $data
     */
    public function __construct($service_type, $channel_name, $channel_code, array $data)
    {
        $this->service_type = $service_type;

        $this->channel_name = $channel_name;

        $this->channel_code = $channel_code;

        $this->data = $data;
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
