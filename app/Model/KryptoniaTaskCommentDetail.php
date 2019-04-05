<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KryptoniaTaskCommentDetail extends Model
{
    protected $table = 'kryptonia_task_comment_detail';

    #relation
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
