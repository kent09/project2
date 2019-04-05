<?php

namespace App\Traits;


use App\Model\UserFollower;

trait UserTrait
{
    protected static function _followUser(int $user_id, int $follower_id) {
        switch( ( new UserFollower() )->saveData(['user_id' => $user_id, 'follower_id' => $follower_id]) ) {
            case 'follow':
                return 'You have successfully follow this user!';
            break;
            case 'un-follow':
                return 'You have successfully un-follow this user!';
            break;
            default:
                return false;
            break;
        }
    }

    protected static function isFollowed(int $user_id, int $follower_id) : bool {
        $followed = UserFollower::where(function($query) use ($user_id, $follower_id) {
            $query->where('user_id', $user_id)
                ->where('follower_id', $follower_id)
                ->where('status', (bool) 1);
        })->first();
        if( $followed )
            return true;
        return false;
    }
}