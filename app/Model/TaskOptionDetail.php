<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskOptionDetail extends Model
{
    //
    protected $table = 'task_option_detail';

    public function saveTaskOptionDetail(int $task_id, bool $attachment) : bool {
        $task = static::where('task_id', $task_id)->first();

        if( $task ) {

            switch ($attachment) {
                case true:
                case 'true':
                case 1:
                    $task->status = (bool) 1;
                    if( $task->save() )
                        return true;
                    return false;
                    break;

                case false:
                case 'false':
                case 0:
                    $task->status = (bool) 0;
                    if( $task->save() )
                        return true;
                    return false;
                    break;
            }

        } else {
            if($attachment){
                $task = new static;
                $task->task_id = $task_id;
                $task->has_attachment = (bool) 1;
                if($task->save())
                    return true;
                return false;
            }
            return true;
        }
    }
}
