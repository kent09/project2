<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipWithdrawal extends Model
{
    protected $appends = ['status_info', 'user_info'];

    public function getStatusInfoAttribute()
    {
        $status = $this->attributes['status'];
        if ($status === 0) {
            return [
                'text' => 'pending',
                'type' => 'warning'
            ];
        } elseif ($status === 1) {
            return [
                'text' => 'cancelled',
                'type' => 'danger'
            ];
        } elseif ($status === 2) {
            return [
                'text' => 'processing',
                'type' => 'info'
            ];
        } elseif ($status === 3) {
            return [
                'text' => 'success',
                'type' => 'success'
            ];
        } elseif ($status === 4) {
            return [
                'text' => 'failed',
                'type' => 'danger'
            ];
        }
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
