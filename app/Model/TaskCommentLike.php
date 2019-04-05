<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskCommentLike extends Model
{
    protected $table = 'task_comment_likes';

    #custom method
    protected function totalLike(int $comment_id) {
        return static::where('comment_id', $comment_id)->where('like', (bool) 1)->where('status', (bool) 1)->count();
    }
    protected function totalUnLike(int $comment_id) {
        return static::where('comment_id', $comment_id)->where('dislike', (bool) 1)->where('status', (bool) 1)->count();
    }
    public function totalRate(int $comment_id) {
        $like = $this->totalLike($comment_id);
        $unlike = $this->totalUnLike($comment_id);
        return compact('like', 'unlike');
    }

    #scope
    public function scopeActive($query) {
        return $query->where('status', (bool) 1);
    }
}
