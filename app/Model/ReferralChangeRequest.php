<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralChangeRequest extends Model
{
    const APPROVE = 1;
    const DECLINE = 2;

    protected $table = 'referral_change_request';

    public function requestor()
    {
    	return $this->belongsTo(User::class, 'requestor_id');
    }

    public function old_referrer()
    {
    	return $this->belongsTo(User::class, 'old_referrer_id');
    }

    public function new_referrer()
    {
    	return $this->belongsTo(User::class, 'new_referrer_id');
    }


    public function status($html = false)
    {
        switch ($this->attributes['status']) {
            case 0:
                if ($html == true) {
                    return '<span class="text-info"><b>Pending</b></span>';
                }
                return 'pending';
            break;

            case 1:
                if ($html == true) {
                    return '<span class="text-success"><b>Granted</b></span>';
                }
                return 'granted';
            break;

            case 2:
                if ($html == true) {
                    return '<span class="text-danger"><b>Declined</b></span>';
                }
                return 'declined';
            break;

            default:
                if ($html == true) {
                    return '<span class="text-info"><b>Pending</b></span>';
                }
                return 'pending';
            break;
        }

    }

    public function getStatusById($id){
        switch ($id) {
            case 0:
                return 'pending';
            break;

            case 1:
                return 'granted';
            break;

            case 2:
                return 'declined';
            break;

            default:
                return 'pending';
            break;
        }
    }

}


