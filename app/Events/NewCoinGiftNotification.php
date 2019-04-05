<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewCoinGiftNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $gifter;
    public $recipient;
    public $coin;
    public $notification;

    public function __construct(User $user, User $recipient, $coin = null, $notification)
    {
        //
        $this->gifter = $user;
        $this->recipient = $recipient;
        $this->coin = $coin;
        $this->notification = $notification;
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

        return 'gift-coin-user-' . $this->recipient->id;
    }
}
