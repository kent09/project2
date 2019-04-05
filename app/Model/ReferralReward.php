<?php

namespace App\Model;

use App\Repository\UtilRepository;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    //

    public function getTypeInfoAttribute()
    {
        switch ($this->attributes['type']) {
            case 1:
                return 'signing';
                break;
            case 9:
                return 'social-connection';
                break;
            default:
                return 'signing';
                break;
        }
    }

    public function type()
    {
        switch ($this->attributes['type']) {
            case 1:
                return 'Signing';
                break;
            case 9:
                return 'Social Connection';
                break;
            default:
                return 'Signing';
                break;
        }
    }

    public function referral()
    {
        return $this->belongsTo(\App\User::class, 'referral_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'referral_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status($html=false)
    {
        $on_hold = (new UtilRepository())->settings('bank_on_hold_duration')->value;

        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['created_at']);
        $diff = Carbon::now()->diffInDays($created);
        if ($diff > $on_hold){
            $this->attributes['status'] = 2;
            $this->save();
        }

        if(isset($this->attributes['status'])){
            switch ($this->attributes['status']){
                case 1:
                    if ($html == true){
                        return '<span class="text-info"><b>Pending</b></span>';
                    }
                    return 'pending';
                    break;
                case 2:
                    if ($html == true) {
                        return '<span class="text-success"><b>Received</b></span>';
                    }
                    return 'received';
                    break;
                case 0:
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
        }else{
            return 'pending';
        }
    }

    public function created_at()
    {
        $type = $this->attributes['type'];
        switch ($type) {
            case 1: // For Signup
                $referral = Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
                if ($referral != null){
                    return $referral->created_at;
                } else {
                    return $this->attributes['created_at'];
                }
                break;
            case 2: // For Social Media Connection
                $social = SocialConnect::where('user_id', $this->attributes['referral_id'])->orderBy('created_at', 'asc')->first();
                if ($social != null){
                    return $social->created_at;
                } else {
                    return $this->attributes['created_at'];
                }
                break;

            default:
                $referral = Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
                if ($referral != null){
                    return $referral->created_at;
                } else {
                    return $this->attributes['created_at'];
                }
                break;
        }
    }

        public function getStatusInfoAttribute()
        {
            $on_hold = settings('bank_on_hold_duration')->value;
            $now = Carbon::now();
            $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->getCreatedInfoAttribute()['created_at']);
            $diff = $now->diffInDays($created);
            if ($diff > $on_hold) {
                $this->attributes['status'] = 2;
                $this->save();
            }
            switch ($this->attributes['status']) {
                case 1:
                    return ['text' => 'pending', 'type' => 'info'];
                    break;
                case 2:
                    return ['text' => 'paid', 'type' => 'success'];
                    break;
                case 0:
                    return ['text' => 'declined', 'type' => 'danger'];
                    break;

                default:
                    return ['text' => 'pending', 'type' => 'info'];
                    break;
            }
        }

    public function getCreatedInfoAttribute()
    {
        $type = $this->attributes['type'];
        if ($type === 1) {
            $referral = \App\Model\Referral::where('user_id', $this->attributes['referral_id'])->where('referrer_id', $this->attributes['user_id'])->first();
            if ($referral !== null) {
                return [
                    'created_at' => $referral->created_at,
                    'etc' => '',
                ];
            } else {
                return [
                    'created_at' => $this->attributes['created_at'],
                    'etc' => '',
                ];
            }
        } else {
            $social = \App\Model\SocialConnect::where('user_id', $this->attributes['referral_id'])->orderBy('created_at', 'asc')->first();
            if ($social !== null) {
                return [
                    'created_at' => $social->created_at,
                    'etc' => $social->social,
                ];
            } else {
                return [
                    'created_at' => $this->attributes['created_at'],
                    'etc' => '',
                ];
            }
        }
    }
}
