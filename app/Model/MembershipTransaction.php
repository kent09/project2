<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipTransaction extends Model
{
    protected $appends = ['status_info', 'user_info', 'role_info'];

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
                'text' => 'success',
                'type' => 'success'
            ];
        } elseif ($status === 2) {
            return [
                'text' => 'failed',
                'type' => 'danger'
            ];
        } elseif ($status === 3) {
            return [
                'text' => 'cancelled',
                'type' => 'danger'
            ];
        } elseif ($status === 4) {
            return [
                'text' => 'void',
                'type' => 'dark'
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

    public function getRoleInfoAttribute()
    {
        $role = \App\Model\Role::find($this->attributes['role_id']);
        if ($role === null) {
            return null;
        }
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
        ];
    }
    
     public function role()
    {
        return $this->belongsTo(\App\Model\Role::class, 'role_id');
    }

    public function status()
    {
        $status = $this->attributes['status'];

        switch($status){
            case 0 :
                return 'pending';
                break;
            case 1;
                return 'success';
                break;
            case 2; 
                return 'failed';
                break;
            default: 
                return 'pending';
                break;
        }
        
    }
}
