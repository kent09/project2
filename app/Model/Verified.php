<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Verified extends Model
{
    protected $table = 'verified';

    const DEFAULT_TYPE = 'Verified';
    const THRESHOLD = "Equivalent of 2500 USDT/week";

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status($html=false)
    {
        $status = $this->attributes['status'];
        if ($status == 1){
            if ($html == true){
                return '<span class="text-success"><b>Verified</b></span>';
            }
            return 'verified';
        } elseif ($status == 2){
            if ($html == true) {
                return '<span class="text-danger"><b>Declined</b></span>';
            }
            return 'declined';
        } else {
            if ($html == true) {
                return '<span class="text-primary"><b>Waiting for Confirmation</b></span>';
            }
            return 'pending';
        }
    }
}
