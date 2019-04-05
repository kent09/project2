<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReputationTask extends Model
{
    //
    protected $table = 'reputation_tasks';

    public function saveReputationTask($task_id, $reputation) {
        $task_reputation = static::where('task_id', $task_id)->first();
        if(!$task_reputation <> null) {
            $reputation_task = new static;
            $reputation_task->reputation = $reputation;
            $reputation_task->task_id = $task_id;
            if($reputation_task->save())
                return true;
            return false;
        }
        return false;
    }

    public function updateReputationTask($task_id, $reputation) {
        $reputation_score = static::where('task_id', $task_id)->first();
        if($reputation_score <> null) {
            switch ( is_null($reputation) ) {
                case true:
                    $reputation_score->active = (boolean) 0;
                    if($reputation_score->save())
                        return true;
                    return false;
                    break;

                case false:
                    $reputation_score->active = (boolean) 1;
                    $reputation_score->reputation = $reputation;
                    if($reputation_score->save())
                        return true;
                    return false;
                    break;
            }
        } else {
            if( !is_null($reputation) )
                return $this->saveReputationTask($task_id, $reputation);
            return true;
        }
        return false;
    }
}
