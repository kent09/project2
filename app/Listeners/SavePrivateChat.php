<?php

namespace App\Listeners;

use App\Events\NewPrivateChat;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class SavePrivateChat
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
     * @param  NewPrivateChat  $event
     * @return void
     */
    public function handle(NewPrivateChat $event)
    {
        $redis = Redis::connection('chat');
        $message = json_encode($event->data);
        $redis->publish('private-chat-channel', $message);

        $chat_id = $event->data['sender'].'_'.$event->data['receiver'];
        if ($event->data['sender'] > $event->data['receiver']){
            $chat_id = $event->data['receiver'].'_'.$event->data['sender'];
        }
        $chat['msg'] = $event->data['msg'];
        $chat['nice'] = $event->data['nice'];
        $chat['username'] = $event->data['username'];
        $chat['datetime'] = $event->data['datetime'];
        $chat['avatar'] = $event->data['avatar'];
        $chat['sender'] = $event->data['sender'];
        $chat['receiver'] = $event->data['receiver'];
        // $chat['image'] = $event->data['image'];
        $chat = json_encode($chat);
        $redis->rpush('chat:private.'.$chat_id, $chat);
    }
}
