<?php

namespace App\Listeners;

use App\Events\NewGroupChat;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class SaveGroupChat
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewGroupChat  $event
     * @return void
     */
    public function handle(NewGroupChat $event)
    {
        $redis = Redis::connection('chat');
        $message = json_encode($event->data);
        $redis->publish('group-chat-channel', $message);

        $chat_id = $event->data['receiver'];
        
        $chat['msg'] = $event->data['msg'];
        $chat['nice'] = $event->data['nice'];
        $chat['username'] = $event->data['username'];
        $chat['datetime'] = $event->data['datetime'];
        $chat['avatar'] = $event->data['avatar'];
        $chat['sender'] = $event->data['sender'];
        $chat['receiver'] = $event->data['receiver'];
        // $chat['image'] = $event->data['image'];
        $chat = json_encode($chat);
        $redis->rpush('chat:group.'.$chat_id, $chat);
    }
}
