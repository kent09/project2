<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KryptoniaTaskSubComment extends Model
{
    protected $table = 'kryptonia_task_sub_comments';
    protected $appends = ['date_created'];

    #mutator
    public function getDateCreatedAttribute() {
        return Carbon::parse($this->attributes['created_at'])->diffForHumans();
    }

    #relation
    public function taskSubCommentDetail() {
        return $this->hasMany(KryptoniaTaskSubCommentDetail::class, 'sub_comment_id', 'id');
    }
    public function taskSubCommentLikeRate() {
        return $this->hasMany(SubTaskCommentLike::class, 'comment_id', 'id');
    }
    public function taskSubCommentUnLikeRate() {
        return $this->hasMany(SubTaskCommentLike::class, 'comment_id', 'id');
    }

    #scope
    public function scopeActive($query) {
        return $query->where('status', (bool) 1);
    }

    #custom
    public function totalComment($comment_id) {
        return static::where('parent_comment_id', $comment_id)->where('status', (bool) 1)->count();
    }
}
