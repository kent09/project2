<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralMembershipEarning extends Model
{
    public function transaction()
    {
        return $this->belongsTo(\App\Model\MembershipTransaction::class, 'transaction_id');
    }

    public function referral() {
        return $this->belongsTo(\App\User::class, 'referral_id');
    }

}
