<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    protected $table = 'blog_post';

    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
