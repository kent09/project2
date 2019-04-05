<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskHidden extends Model
{
    //
    protected $table = 'task_hiddens';

    public $timestamps = false;

    public function saveData(array $data) : bool {
        $hid = new static;
        $hid->task_id = $data['task_id'];
        $hid->user_id = $data['user_id'];
        if( $hid->save() )
            return true;
        return false;
    }

    #relation
    public function task() {
        return $this->hasMany(Task::class, 'id', 'task_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
