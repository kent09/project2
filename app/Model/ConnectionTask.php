<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ConnectionTask extends Model
{
    //
    protected $table = 'connection_tasks';

    public function saveConnectionTask(int $task_id, bool $connection) : bool {
        $connection_task = static::where('task_id', $task_id)->first();
        if( $connection_task ) {
            switch ($connection) {
                case true:
                case 1:
                    $connection_task->status = (bool) 1;
                    $connection_task->connection = 0;
                    if($connection_task->save())
                        return true;
                    return false;
                    break;

                case false:
                case 0:
                    $connection_task->status = (bool) 0;
                    $connection_task->connection = 0;
                    if($connection_task->save())
                        return true;
                    return false;
                    break;
            }
        } else {
            if($connection){
                $connection_task = new static;
                $connection_task->task_id = $task_id;
                $connection_task->connection = 0;
                if($connection_task->save())
                    return true;
                return false;
            }
            return true;
        }
    }

}
