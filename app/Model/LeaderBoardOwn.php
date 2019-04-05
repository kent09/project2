<?php

namespace App\model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaderBoardOwn extends Model
{
    protected $table = 'leader_board_own';

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
