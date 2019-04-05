<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BtcWallet extends Model
{
    protected $table = 'btc_wallets';
    
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
