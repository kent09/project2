<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskWizard extends Model
{
    //
    protected $table = 'task_wizard';

    public function taskFromWizard(int $task_id) {
        $from_wizard = static::where('task_id', $task_id)->first();
        if( $from_wizard )
            return true;
        return false;
    }

    public function saveTaskFromWizard(int $task_id, int $user_id) {
        $task_from_wizard = new static;
        $task_from_wizard->task_id = $task_id;
        $task_from_wizard->user_id = $user_id;
        if($task_from_wizard->save())
            return true;
        return false;
    }
}
