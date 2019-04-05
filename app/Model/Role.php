<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    protected $appends = ['price', 'limitations'];

    public function getPriceAttribute()
    {
        return MembershipPrice::where('role_id', $this->attributes['id'])->first()->makeHidden(['role_id', 'created_at', 'updated_at']);
    }

    public function getLimitationsAttribute()
    {
        return Limitation::where('role_id', $this->attributes['id'])->where('status', 1)->get()->makeHidden(['role_id', 'created_at', 'updated_at']);
    }

    public function limitations()
    {
        return $this->hasMany(\App\Model\Limitation::class, 'role_id');
    }
}
