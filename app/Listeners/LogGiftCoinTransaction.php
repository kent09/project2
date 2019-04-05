<?php

namespace App\Listeners;

use App\Events\NewNotificationCount;
use App\Events\NewCoinGiftNotification;
use App\Helpers\KryptonianRedis;
use App\Model\Notification;
use App\Events\NewGiftCoin;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogGiftCoinTransaction extends KryptonianRedis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    protected $redis_db;
    protected $key;
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
     * @param  NewGiftCoin  $event
     * @return void
     */
    public function handle(NewGiftCoin $event)
    {
        //
        $data = [
            'type' => $event->type,
            'gifter' => $event->gifter,
            'recipient' => $event->recipient,
            'sup' => $event->sup,
            'memo' => $event->memo,
        ];

        $this->logNewGiftCoinTransaction($data);
    }

    private function logNewGiftCoinTransaction($data = []) {

        $datum = [
            'sender_id' => $data['gifter']->id,
            'recipient_id' => $data['recipient']->id,
            'title' => 'Gift-Coin',
            'description' => static::notifyDescription($data['type'], $data['gifter'], $data['sup'], $data['memo']),
            'type' => 'Gift-Coin',
            'task_id' => 0
        ];

        $notification = (new Notification())->saveTransaction($datum);

        if($notification) {

            $countData = ['user_id' => $data['recipient']->id, 'notification_count' => 1];

            event(new NewCoinGiftNotification($data['gifter'], $data['recipient'], $data['sup'], $notification));

            event(new NewNotificationCount($countData));

            return true;
        }

        return false;
    }

    private static function notifyDescription($type, $gifter, $sup, $memo) {

        $description = '';

        if($type === 'gift-coin') {
            if($memo !== '') {
                $description .= '<span class="text-black text-bold">' . $gifter->name . '</span> Sent you <span class="text-black">' . $sup . ' SUP</span> with a memo: <br>' . '<span class="text-black" style="font-style: italic">' . '"'. $memo .'"' . '</span>';
            } else {

                $description .= $gifter->name . ' Sent you ' . $sup . ' SUP';
            }

        }

        return $description;
    }
}
