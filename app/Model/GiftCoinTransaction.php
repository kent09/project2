<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GiftCoinTransaction extends Model
{
    protected $table = 'gift_coin_transactions';


    public function saveGiftTransaction($data = []) {

        $this->attributes['sender_id'] = $data['sender_id'];
        $this->attributes['receiver_id'] = $data['receiver_id'];
        $this->attributes['coin'] = $data['gift_coin'];
        $this->attributes['memo'] = $data['gift_memo'];
        if($this->save())
            return $this;
        return false;
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
