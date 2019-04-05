<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AdminActivity extends Model
{
    protected $connection = 'mysql_tracer';
    
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'affected_user_id');
    }

    public function type($html=false)
    {
        $type = $this->attributes['type'];
        switch ($type) {
            case 0:
                if ($html == true){
                    return "<span class='text-primary'><b>Fetch</b></span>";
                }
                return 'fetch';
                break;
            case 1:
                if ($html == true) {
                    return "<span class='text-success'><b>Create</b></span>";
                }
                return 'create';
                break;
            case 2:
                if ($html == true) {
                    return "<span class='text-warning'><b>Update</b></span>";
                }
                return 'update';
                break;
            case 3:
                if ($html == true) {
                    return "<span class='text-danger'><b>Delete</b></span>";
                }
                return 'delete';
                break;
            
            default:
                if ($html == true) {
                    return "<span class='text-primary'><b>Fetch</b></span>";
                }
                return 'fetch';
                break;
        }
    }

    public function status($html=false)
    {
        $status = $this->attributes['status'];
        switch ($status) {
            case 0:
                if ($html == true) {
                    return "<span class='text-danger'><b>Failed</b></span>";
                }
                return 'failed';
                break;
            case 1:
                if ($html == true) {
                    return "<span class='text-success'><b>Success</b></span>";
                }
                return 'success';
                break;
            
            default:
                if ($html == true) {
                    return "<span class='text-success'><b>Success</b></span>";
                }
                return 'success';
                break;
        }
    }
}
