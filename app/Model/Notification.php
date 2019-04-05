<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    public function user() {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function saveTransaction($data = []) {

        $this->attributes['sender_id'] = $data['sender_id'];
        $this->attributes['recipient_id'] = $data['recipient_id'];
        $this->attributes['title'] =  $data['title'];
        $this->attributes['description'] = $data['description'];
        $this->attributes['type'] = $data['type'];
        $this->attributes['task_id'] = $data['task_id'];

        if($this->save())
            return $this;
        return false;
    }

    public function deleteNotification($id){
        $notification = static::where('id', $id)->first();
        if($notification){
            $notification->is_deleted = 1;
            if($notification->save()){
                return true;
            }
        }
        return false;
    }
    
}
