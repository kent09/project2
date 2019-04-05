<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralByLevel extends Model
{
    protected $table = 'referral_by_level';

    public function referral() {
        return $this->belongsTo(\App\User::class, 'referral_id');
    }
}
