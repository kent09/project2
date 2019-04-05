<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserCookie extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
