<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DbWithdrawal extends Model
{
    //
	protected $table = 'dbwithdrawal';
	const FOR_APPROVAL_STATUS = 10;

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function txid()
    {
    	return $this->belongsTo('App\Txid');
    }

    public function status($html=false)
    {
    	switch ($this->attributes['status']) {
			case 0:
				if($html){
					return '<span class="text-warning"><b>Unverified</b></span>';
				}else{
					return 'Unverified';
				}
    			break;
			case 1:
				if($html){
					return '<span class="text-info"><b>Verified</b></span>';
				}else{
					return 'Verified';
				}
    			break;
			case 2:
				if($html){
					return '<span class="text-info"><b>Processing</b></span>';
				}else{
					return 'Processing';
				}
    			break;
			case 3:
				if($html){
					return '<span class="text-info"><b>Complete</b></span>';
				}else{
					return 'Complete';
				}
    			break;
			case 7:
				if($html){
					return '<span class="text-info"><b>Canceled (7)</b></span>';
				}else{
					return 'Canceled (7)';
				}
                break;
			case 8:
				if($html){
					return '<span class="text-info"><b>Locked</b></span>';
				}else{
					return 'Locked';
				}
    			break;
			case 9:
				if($html){
					return '<span class="text-info"><b>Expired (9)</b></span>';
				}else{
					return 'Expired (9)';
				}
				break;
			case 10:
				if($html){
					return '<span class="text-info"><b>For Approval</b></span>';
				}else{
					return 'For Approval';
				}
				break;
			case 11:
				if($html){
					return '<span class="text-danger"><b>Declined</b></span>';
				}else{
					return 'Declined';
				}
				break;
			case 14:
				if($html){
					return '<span class="text-info"><b>Failed (14)</b></span>';
				}else{
					return 'Failed (14)';
				}
				break;
			case 17:
				if($html){
					return '<span class="text-info"><b>Failed (17)</b></span>';
				}else{
					return 'Failed (17)';
				}
				break;
			default:
				if($html){
					return '<span class="text-info"><b>Processing</b></span>';
				}else{
					return 'Processing';
				}
    			break;
    	}
	}

}
