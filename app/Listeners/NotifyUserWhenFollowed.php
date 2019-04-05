<?php

namespace App\Listeners;

use App\User;
use App\Model\Notification;
use App\Model\UserFollower;
use App\Helpers\KryptonianRedis;
use App\Events\NewFollowNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUserWhenFollowed extends KryptonianRedis
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
        $this->key = 'follower_list';
        $this->redis_db = 5;
    }

    /**
     * Handle the event.
     *
     * @param  NewFollowNotification  $event
     * @return void
     */
    public function handle(NewFollowNotification $event)
    {
        //
        $this->notifyUser($event->user, $event->follower, $event->broadcastAs());
    }

    protected function notifyUser(User $user, User $follower, $channel) {

        $data = [
          'sender_id' => $follower->id,
          'recipient_id' => $user->id,
          'title' => 'Followed',
          'description' => ($follower->name ? $follower->name : $follower->username) . ' followed you!',
          'type' => 'Follow',
          'task_id' => 0
        ];

        (new Notification())->saveTransaction( $data );

        $this->redisDb( $this->redis_db );

        $this->_initRedis( $channel, $this->key, 'rPush', true, $this->prepData( $data, $user->id ) );
    }

    protected function prepData(array $data, $user_id = null) {
        return json_encode(
            $data = [
                'value_id' => $this->hashId($user_id),
                'sender_id' => $data['sender_id'],
                'receiver_id' => $user_id,
                'title' => $data['title'],
                'subject' => $data['description'],
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
