<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class TaskUser extends Model
{
    //
    protected $table = 'task_user';

    #relation
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function creator() {
        return $this->belongsTo(User::class, 'task_creator');
    }

    public function taskInfo(){
        return $this->belongsTo(Task::class,'task_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function attachment() {
        return $this->hasMany(TaskCompletionDetail::class, 'task_id', 'task_id');
    }

    public function taskPoints()
    {
        return $this->hasMany(\App\ReferralTaskPoint::class, 'task_id', 'task_id');
    }

    #custom
    public function saveData(array $data) : bool {
        $task_user = new static;
        $task_user->user_id = $data['user_id'];
        $task_user->task_id = $data['task_id'];
        $task_user->reward = $data['reward'];
        $task_user->task_creator = $data['task_creator'];
        if( $task_user->save() )
            return true;
        return false;
    }

    public function getStatusInfoAttribute()
    {
        $status = $this->attributes['status'];
        if ($status === 1) {
            return [
                'text' => 'paid',
                'type' => 'success'
            ];
        } else {
            return [
                'text' => 'pending',
                'type' => 'warning'
            ];
        }
    }
}
