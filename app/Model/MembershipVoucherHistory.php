<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipVoucherHistory extends Model
{
    protected $appends = ['status_info', 'payer_info', 'user_info'];

    public function payer()
    {
        return $this->belongsTo(\App\User::class, 'payer_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Model\MembershipTransaction::class, 'trans_id');
    }

    public function getStatusInfoAttribute()
    {
        $status = $this->attributes['status'];
        if ($status === 0) {
            return [
                'text' => 'processing',
                'type' => 'info'
            ];
        } elseif ($status === 1) {
            return [
                'text' => 'unsused',
                'type' => 'success'
            ];
        } elseif ($status === 2) {
            return [
                'text' => 'used',
                'type' => 'danger'
            ];
        }
    }

    public function getPayerInfoAttribute()
    {
        $user = \App\User::find($this->attributes['payer_id']);
        if ($user === null) {
            return null;
        }
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
        ];
    }

    public function getUserInfoAttribute()
    {
        $user = \App\User::find($this->attributes['user_id']);
        if ($user === null) {
            return null;
        }
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
        ];
    }
}
