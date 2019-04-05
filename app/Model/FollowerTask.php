<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FollowerTask extends Model
{
    //
    protected $table = 'follower_tasks';

    public function saveFollowerTask($user_id, $task_id) {
        if($user_id && $task_id) {
            $follower_task = new static;
            $follower_task->task_user_id = $user_id;
            $follower_task->task_id = $task_id;
            if($follower_task->save())
                return true;
        }
        return false;
    }

    public function updateFollowerTask(int $task_id, bool $followed) : bool {
        $follower_task = static::where('task_id', $task_id)->first();
        if($follower_task) {
            switch ($followed) {
                case true:
                case 'true':
                case 1:
                    $follower_task->active = (bool) 1;
                    if($follower_task->save())
                        return true;
                    return false;
                    break;

                case false:
                case 'false':
                case 0:
                $follower_task->active = (bool) 0;
                if($follower_task->save())
                    return true;
                return false;
                break;
            }
        } else {
            if($followed){
                if( $this->saveFollowerTask(Auth::id(), $task_id) )
                    return true;
                return false;
            }
            return true;
           
        }
        return false;
    }
}
