<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    public function role()
    {
        return $this->belongsTo(\App\Model\Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(\App\Model\Permission::class, 'permission_id');
    }
}
