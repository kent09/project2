<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class NewUnfollowNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $user;
    public $follower;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, User $follower)
    {
        //
        $this->user = $user;
        $this->follower = $follower;
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

    public function broadcastAs() {

        return 'unfollowed-' . $this->user->id;
    }
}
