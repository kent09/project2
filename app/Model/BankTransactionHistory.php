<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BankTransactionHistory extends Model
{
    protected $table = 'bank_transaction_histories';

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(){
        return $this->belongsTo(BankTransaction::class, 'trxn_id');
    }
}
