<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Roadmap extends Model
{
    protected $table = 'roadmaps';

    public function admin() {
        return $this->belongsTo(User::class,'admin_id');
    }

    public function getMonth($month_id){
        switch($month_id){
            case 1:
                return 'January';
                break;
            case 2:
                return 'February';
                break;    
            case 3:
                return 'March';
                break;    
            case 4:
                return 'April';
                break;    
            case 5:
                return 'May';
                break;    
            case 6:
                return 'June';
                break;    
            case 7:
                return 'July';
                break;    
            case 8:
                return 'August';
                break;    
            case 9:
                return 'September';
                break;    
            case 10:
                return 'October';
                break;    
            case 11:
                return 'November';
                break;    
            case 12:
                return 'December';
                break;    
            default : 
                return '';
                break;
        }
    }
}
