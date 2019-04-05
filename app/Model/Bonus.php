<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    //
    protected $table = 'bonus';

    #relation
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function month_coins(){
        $coins = [
            '0' => 'fb_coin',
            '1' => 'jan_coin',
            '2' => 'feb_coin',
            '3' => 'mar_coin',
            '4' => 'apr_coin',
            '5' => 'may_coin',
            '6' => 'jun_coin',
            '7' => 'july_coin',
            '8' => 'aug_coin',
            '9' => 'sept_coin',
            '10' => 'oct_coin',
            '11' => 'nov_coin',
            '12' => 'dec_coin'
        ];

        return $coins;
    }

    public static function get_month_coin_desc($month_coin){
        
        switch($month_coin){
            case 'jan_coin':
                return 'January';
                break;
            case 'feb_coin':
                return 'February';
                break;
            case 'mar_coin':
                return 'March';
                break;
            case 'apr_coin':
                return 'April';
                break;
            case 'may_coin':
                return 'May';
                break;
            case 'jun_coin':
                return 'June';
                break;
            case 'july_coin':
                return 'July';
                break;
            case 'aug_coin':
                return 'August';
                break;
            case 'sept_coin':
                return 'September';
                break;
            case 'oct_coin':
                return 'October';
                break;
            case 'nov_coin':
                return 'November';
                break;
            case 'dec_coin':
                return 'December';
                break;
            case 'fb_coin':
                return '3 month bonus';
                break;
            default:
                return 0;
                break;

        }
    }
}
