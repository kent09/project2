<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtherWithdrawl extends Model
{
    protected $table = 'ether_withdrawl';

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
