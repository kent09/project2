<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class BtcWithdrawal extends Model
{
    const FOR_APPROVAL_STATUS = 10;
    
    protected $table = 'bitcoinwithdrawl';

    #relation
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status($html = false){
        $status = $this->attributes['status'];

        switch ($status) {
            case 0:
				if($html){
					return '<span class="text-warning"><b>Waiting for confirmation</b></span>';
				}else{
					return 'Waiting for confirmation';
				}
                break;
            case 1:
                if($html){
                    return '<span class="text-info"><b>Completed</b></span>';
                }else{
                    return 'Completed';
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
            case 99:
                if($html){
                    return '<span class="text-info"><b>Failed</b></span>';
                }else{
                    return 'Failed';
                }
                break;
            default:
                return '';
                break;
        }
    }
}
