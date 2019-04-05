<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Premine extends Model
{
    //
    protected $table = 'premined';

    #relation
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
