<?php
/*
 * A simple wrapper for redis
 * @author rnel <arnacmj@gmail.com>
 * */

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;
use App\User;


class KryptonianRedis
{
    #index to be used
    protected $redis_db = 0;

    #redis instance
    protected $redisInstance;

    public function __construct()
    {
        $this->redisConnection();
    }

    protected function prepData(array $data) {

        return json_encode(
            $data, JSON_NUMERIC_CHECK #make sure integer type persist
        );
    }

    protected function redisConnection() {
        return $this->redisInstance = Redis::connection();
    }

    protected function redisDb($db) {
        if($db)
            return $this->redisInstance->select($db);
        return $this->redisInstance->select($this->redis_db);
    }

    protected function hashId($id) {
        $hash = hash('md5', $id);
        return $hash;
    }

    protected function hasAvatar($user_id) {
        $user = User::find($user_id);
        if($user->has_avatar === 1)
            return 1;
        return 0;
    }

    protected function _initRedis( $channel = null, $key = null, $action, $publish = null, $data, $count = 0 ) {

        #need to publish?
        if($publish)
            $this->redisInstance->publish( $channel, $data ); #todo make a method for further filtering data

        #check redis api action
        if( $action ) {
            #expose redis api here if necessary.
            switch ($action) {
                case 'rPush':
                    $this->redisInstance->rPush( $key, $data ); #todo make a method for further filtering data
                    break;
                case 'lRem':
                    #remove exactly 1 in data, default to 0;
                    if( $this->redisInstance->exists( $key ) )
                        $this->redisInstance->lRem( $key, $count, $data ); #todo make a method for further filtering data
                    break;
            }
        }
    }
}
