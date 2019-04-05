<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
