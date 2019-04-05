<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ActivityScoreTask extends Model
{
    //
    protected $table = 'activity_score_tasks';

    public function saveActivityScoreTask($task_id, $activity_score) {
        $task_activity_score = static::where('task_id', $task_id)->first();

        if($task_activity_score == null) {
            $task_activity_score = new static;
            $task_activity_score->activity_score = $activity_score;
            $task_activity_score->task_id = $task_id;
            if($task_activity_score->save())
                return true;
            return false;
        }
        return false;
    }

    public function updateActivityScoreTask($task_id, $activity_score) {
        $activity = static::where('task_id', $task_id)->first();
        if($activity <> null) {
            switch ( is_null($activity_score) ) {
                case true:
                    $activity->active = (boolean) 0;
                    if($activity->save())
                        return true;
                    return false;
                    break;
                case false:
                    $activity->active = (boolean) 1;
                    $activity->activity_score = $activity_score;
                    if($activity->save())
                        return true;
                    return false;
                    break;
            }
        } else {
            if( !empty($activity_score) )
                return $this->saveActivityScoreTask($task_id, $activity_score);
            return true;
        }
        return false;
    }
}
