<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BlockedUser extends Model
{
    protected $table = 'blocked_users';

    //added by monmon
    public function user(){
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
