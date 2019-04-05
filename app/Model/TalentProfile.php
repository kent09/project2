<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TalentProfile extends Model
{
    protected $table = 'talent_profile';

    public function user() {

        return $this->belongsTo(User::class, 'user_id');
    }

    public function toggleProfileStatus($data = []){
        $status =  static::where('user_id',$data['user_id'])
                          ->update(['status' => $data['status']]); 
        return $status;        
    }
}
