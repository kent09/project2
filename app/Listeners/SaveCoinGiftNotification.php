<?php

namespace App\Listeners;

use App\User;
use App\Events\NewCoinGiftNotification;
use App\Helpers\KryptonianRedis;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SaveCoinGiftNotification extends KryptonianRedis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    protected $key;
    protected $redis_db;
    public function __construct()
    {
        //
        parent::__construct();
        $this->redis_db = 5;
        $this->key = 'gift_coin_list';
    }

    /**
     * Handle the event.
     *
     * @param  NewCoinGiftNotification  $event
     * @return void
     */
    public function handle(NewCoinGiftNotification $event)
    {
        //
        $data = [
            'sender_id' => $event->gifter->id,
            'recipient_id' => $event->recipient->id,
            'coin' => $event->coin,
            'type' => 'Gift Coin'
        ];
        $this->_init( $data, $event->broadcastAs() );
    }

    protected function _init($data, $channel) {
        $this->redisDb( $this->redis_db );

        $this->_initRedis ($channel, $this->key, 'rPush', true, $this->prepData( $data, $data['recipient_id']) );
    }

    protected function prepData(array $data, $user_id = null) {
        return json_encode(
            $data = [
                'value_id' => $this->hashId($user_id),
                'sender_id' => $data['sender_id'],
                'receiver_id' => $user_id,
                'title' => $data['type'],
                'subject' => $data['coin'] . ' SUP',
                'sender_name' => $this->senderName($data['sender_id']),
                'type' => $data['type'],
                'avatar' => $this->hasAvatar($data['sender_id'])
            ]
        );
    }

    protected function senderName($user_id) {
        $user = User::find($user_id);
        return $user->name;
    }

}
