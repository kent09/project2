<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewDeleteTaskCommentDetailEvent 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     /**
     * Create a new event instance.
     *
     * @return void
     */
    public $comment_detail;
    public function __construct($comment_detail_id)
    {
        //
        $this->comment_detail = $comment_detail_id;
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
