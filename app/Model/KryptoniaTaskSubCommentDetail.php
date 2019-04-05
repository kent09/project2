<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KryptoniaTaskSubCommentDetail extends Model
{
    protected $table = 'kryptonia_task_sub_comment_details';

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
