<?php

namespace App\Model;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BannedUserTask extends Model
{
    //
    protected $table = 'banned_user_task';

    protected $appends = ['carbon_created_at'];

    #custom
    public function bannedUserFromTask(array $data = []) : bool {
        $banned = new static;
        $banned->user_id = $data['user_id'];
        $banned->task_id = $data['task_id'];
        $banned->reason = $data['reason'];
        if( $banned->save() )
            return true;
        return false;
    }

    #mutator
    public function getCarbonCreatedAtAttribute() {
        return app('carbon')->parse($this->attributes['created_at'])->diffForHumans();
    }

    #relation
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
