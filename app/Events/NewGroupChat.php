<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Auth;
use App\Traits\Manager\UserTrait;

class NewGroupChat implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels, UserTrait;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($msg=null, $receiver, $sender)
    {
        $this->data = [
            'msg' => $msg,
            'nice' => crc32(Auth::id()),
            'username' => Auth::user()->username,
            'datetime' => date('Y-m-d H:i A'),
            'avatar' => 'https://kimg.io/image/profiles/'.Auth::id().'/avatar.png',
            'sender' => $sender,
            'receiver' => $receiver,
        ];
    }

     /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
    }
}
