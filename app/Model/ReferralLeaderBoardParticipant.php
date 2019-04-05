<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ReferralLeaderBoardParticipant extends Model
{
    protected $table = 'referral_leader_board_participants';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getReferralCountByLevel($param){
        extract($param);

        $group_lists = ['weekly_list','monthly_list','all_time_list'];

        if($level == ''){
            $level = '1st';
        }
        
        
        $count_referral = 0;
        $level_count = [];
        $groups = [];
        $participants = static::where('user_id', $user_id)->first();
        if($participants){
            if($group_list == ""){
                foreach($group_lists as $key => $group){
                    if($participants->$group <> '[]'){
                        $groups[] = json_decode($participants->$group,true);
                    }
                }

                foreach($groups as $key => $val){
                    foreach($val as $ref){
                        if($ref['level'] == $level){
                            if($ref['rewards'] <> 0){
                                array_push($level_count,$ref);
                                $level_count = array_unique($level_count,SORT_REGULAR);
                            }
                        }
                    }
                }

            }else{
                $participants_list = json_decode($participants->$group_list);
                if(count($participants_list) > 0){
                    foreach ($participants_list as $item){
                        if($item->level == $level){
                            if($item->rewards <> 0){
                                array_push($level_count,$item);
                                $level_count = array_unique($level_count,SORT_REGULAR);
                            }
                        }
                    }
                    
                }
            }
            
        }

        $count_referral = count($level_count);

        return $count_referral;
    }
}
