<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KryptoniaTaskComment extends Model
{
    protected $table = 'kryptonia_task_comments';

    protected $appends = ['date_created'];

    #relation
    public function taskCommentDetail() {
        return $this->hasMany(KryptoniaTaskCommentDetail::class, 'comment_id', 'id');
    }
    public function taskCommentLikeRate() {
        return $this->hasMany(TaskCommentLike::class, 'comment_id', 'id');
    }
    public function taskCommentUnLikeRate() {
        return $this->hasMany(TaskCommentLike::class, 'comment_id', 'id');
    }
    public function taskSubComment() {
        return $this->hasMany(KryptoniaTaskSubComment::class, 'parent_comment_id', 'id');
    }

    #mutator
    public function getDateCreatedAttribute() {
        return Carbon::parse($this->attributes['created_at'])->diffForHumans();
    }

    #scope
    public function scopeActive($query) {
        return $query->where('status', (bool) 1);
    }
}
