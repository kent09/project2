<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralTaskPoint extends Model
{
    protected $appends = ['referral_status_info', 'referral_user_status_info', 'referral_version_info'];

    public function getReferralStatusInfoAttribute()
    {
        $referral = \App\Model\Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
        if ($referral === null) {
            return null;
        }
        return $referral->getStatusInfoAttribute()['text'];
    }

    public function getReferralUserStatusInfoAttribute()
    {
        $referral = \App\Model\Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
        if ($referral === null) {
            return null;
        }
        return $referral->referral->getStatusInfoAttribute()['text'];
    }

    public function getReferralVersionInfoAttribute()
    {
        $referral = \App\Model\Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
        if ($referral === null) {
            return null;
        }
        return $referral->version;
    }
}
