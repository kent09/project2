<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VisitorCounter extends Model
{
    public function user()
    {
    	return $this->belongsTo('App\User', 'user_id');
    }
}
