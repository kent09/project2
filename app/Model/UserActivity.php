<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{   
    protected $connection = 'mysql_tracer';

    public function userLogginID()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
