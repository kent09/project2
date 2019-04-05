<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqsHistory extends Model
{
    protected $table = 'faqs_histories';

    public function admin(){
        return $this->belongsTo(User::class, 'admin_id');
    }
}
