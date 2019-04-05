<?php

namespace App\Model;
use App\Model\SocialConnectStatus;

use Illuminate\Database\Eloquent\Model;

class SocialConnectHistory extends Model
{
    protected $table = 'social_connect_history';
    
    public function social()
    {
        return $this->belongsTo(SocialMedia::class, 'social_id');
    }
     #custom
    public function saveData(array $data) : bool {
        $sc = new static;
        $sc->user_id = $data['user_id'];
        $sc->social_id = $data['social_id'];
        $sc->account_name = $data['account_name'];
        $sc->account_id = $data['account_id'];
        $sc->status = $data['status'];

        if(isset($data['hard_unlink_status'])){
            $sc->hard_unlink_status = $data['hard_unlink_status'];
        }   
        if(isset($data['hard_unlink_reason'])){
            $sc->hard_unlink_reason = $data['hard_unlink_reason'];
        }   
        if(isset($data['disapproved_reason'])){
            $sc->disapproved_reason = $data['disapproved_reason'];
        }   
        if(isset($data['version'])){
            $sc->version = $data['version'];
        }  
        if($sc->save())
            return true;
        return false;
    }

    public function hardUnlinkStatus()
    {
        $status = $this->attributes['hard_unlink_status'];
        
        if($status == 1){
            return 'Waiting for approval';
        }else if($status == 2){
            return 'Disapproved';
        }else if($status == 3){
            return 'Approved';
        }else{
            return '';
        }
    }

    public function socialConnectStatus()
    {
        $status = $this->attributes['status'];

        $social_con = SocialConnectStatus::find($status);
        
        if($social_con){
            return $social_con->status;
        }else{
            return '';
        }
    }
}
