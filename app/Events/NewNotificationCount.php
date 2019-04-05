<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewNotificationCount extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $user_id;
    public $notification_count;

    public function __construct($data = [])
    {
        //
        $this->user_id = @$data['user_id'];
        $this->notification_count = @$data['notification_count'];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['notification-count'];
    }

    public function broadcastAs() {

        return 'user-' . $this->user_id;
    }
}
