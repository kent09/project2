<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewGiftCoin
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $type;
    public $recipient;
    public $gifter;
    public $sup;
    public $memo;

    public function __construct(Authenticatable $auth, User $user, $type = null, $sup = 0, $memo = null)
    {
        //
        $this->gifter = $auth;
        $this->recipient = $user;
        $this->type = $type;
        $this->sup = $sup;
        $this->memo = $memo;
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
