<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralChangeTransactions extends Model
{
    protected $table = 'referral_change_transactions';

    public function admin()
    {
    	return $this->belongsTo(User::class, 'admin_id');
    }

    public function referral_request()
    {
    	return $this->belongsTo(ReferralChangeRequest::class, 'referral_req_id');
    }

    public function referral()
    {
    	return $this->belongsTo(Referral::class, 'referral_tbl_id');
    }

}
