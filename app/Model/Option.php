<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public function poll()
    {
    	return $this->belongsTo(Poll::class, 'poll_id');
    }

    public function values()
    {
    	return $this->hasMany(VoteValue::class, 'option_id');
    }

    public function points()
    {
    	return VoteValue::where('option_id', $this->attributes['id'])->sum('votes');
    }
}
