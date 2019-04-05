<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonusTransactions extends Model
{
    protected $table = 'bonus_transactions';
 
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
