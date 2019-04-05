<?php

namespace App\Model;

use App\Model\SocialConnectHistory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class SocialConnect extends Model
{
    /**
     * Status
     * 
     * 1 = linked
     * 2 = soft-unlinked
     * 3 = hard-unlinked
     */

    /**
     * Hard Unlink Status
     * 
     * 1 = requested
     * 2 = declined
     * 3 = approved
     */
    
     const hu_status_requested = 1;
     const hu_status_declined = 2;
     const hu_status_approved = 3;

    protected $table = 'social_connects';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #custom
    public function saveData(array $data) : bool {
        $sc = new static;
        $sc->user_id = $data['user_id'];
        $sc->social = $data['social'];
        $sc->account_name = $data['account_name'];
        $sc->account_id = $data['account_id'];
        $sc->version = 2;
        $sc->status = 1;
        if($sc->save())
            $data['status'] = $sc->status;
            $saveHistory = SocialConnectHistory::saveData($data);
            return $saveHistory;
        return false;
    }

    public function status($html = false)
    {
        $on_hold = settings('bank_on_hold_duration')->value;
        $status = $this->attributes['status'];
        $version = $this->attributes['version'];
        if ($version == 1){
            if ($html == true) {
                return '<span class="text-success"><b>Received</b></span>';
            }
            return 'received';
        } elseif ($version == 2){
            $now = Carbon::now();
            $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['created_at']);
            $days = $now->diffInDays($created);
            if ($days > $on_hold){
                if ($html == true) {
                    return '<span class="text-success"><b>Received</b></span>';
                }
                return 'received';
            } else {
                if ($status == 1){
                    if ($html == true) {
                        return '<span class="text-warning"><b>On-hold</b></span>';
                    }
                    return 'on-hold';
                } else {
                    if ($html == true) {
                        return '<span class="text-danger"><b>Aborted</b></span>';
                    }
                    return 'aborted';
                }
            }
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
