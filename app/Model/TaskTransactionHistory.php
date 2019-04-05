<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskTransactionHistory extends Model
{
    //
    protected $table = 'task_transaction_histories';

    public function task() {
        return $this->belongsTo(Task::class, 'task_id');
    }

    #custom
    public function saveData(array $data) : bool {
        $task_history = new static;
        $task_history->task_id = $data['task_id'];
        $task_history->transaction_type = $data['type'];
        $task_history->history = $data['history'];
        $task_history->user_id = $data['user_id'];
        if( $task_history->save() )
            return true;
        return false;
    }
}
