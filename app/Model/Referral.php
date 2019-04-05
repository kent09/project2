<?php

namespace App\Model;

use App\Repository\UtilRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\User;

class Referral extends Model
{
    public function referral()
    {
    	return $this->belongsTo(User::class, 'user_id');
    }

    public function referrer()
    {
    	return $this->belongsTo(User::class, 'referrer_id');
    }

    public function status($html = false)
    {
        $on_hold = (new UtilRepository())->settings('bank_on_hold_duration')->value;
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['created_at']);
        $diff = Carbon::now()->diffInDays($created);
        if ($diff > $on_hold) {
            $this->attributes['status'] = 2;
            $this->save();
        }

        switch ($this->attributes['status']) {
            case 1:
                if ($html == true) {
                    return '<span class="text-info"><b>Pending</b></span>';
                }
                return 'pending';
                break;
            case 2:
                if ($html == true) {
                    return '<span class="text-success"><b>Paid</b></span>';
                }
                return 'paid';
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
    }    

    public function getSecondLevelCountAttribute()
    {
        return \App\Model\Referral::where('referrer_id', $this->attributes['user_id'])->count();
    }

    public function getStatusInfoAttribute()
    {
        $on_hold = settings('bank_on_hold_duration')->value;
        $now = Carbon::now();
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['created_at']);
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

    public function getTaskPointsAttribute()
    {
        return $this->attributes['points'] + $this->attributes['second_lvl_points'] + $this->attributes['third_lvl_points'];
    }

    public function getRewardsAttribute()
    {
        return $this->attributes['points'] + $this->attributes['second_lvl_points'] + $this->attributes['third_lvl_points'];
    }
}
