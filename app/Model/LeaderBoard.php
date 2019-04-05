<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LeaderBoard extends Model
{
     protected $table = 'leader_boards';

     /**
     * @param $col, $userid
     *
     * @return string
     */
     public static function getLeaderBoardRankByRange($param){
         extract($param);
         if($col == ""){
            $col = "all_time_top";
         }

         $rank = "";

         if($userid == ""){
            $userid = Auth::id();

         }
         
         $info = static::select($col,'id')->get();
         
         if(count($info) > 0){
             foreach($info as $leaderboard){
                $top = json_decode($leaderboard->$col);
                if($top){
                    foreach($top as $key => $val){
                        if($key == $userid){
                            $rank = $leaderboard->id;
                        }
                    }
                }
             }
         }
         return $rank;
     }

     
     /**
     * @param $col, $userid
     *
     * @return string
     */
    public static function getLeaderBoardInfoByRange($param){
        extract($param);
        if($col == ""){
           $col = "all_time_top";
        }

        $rank = "";

        if($userid == ""){
           $userid = Auth::id();

        }
        
        $info = static::select($col,'id')->get();
        $data = [];
        if(count($info) > 0){
            foreach($info as $leaderboard){
               $top = json_decode($leaderboard->$col);
               if($top){
                   foreach($top as $key => $val){
                       if($key == $userid){
                           $data = [
                               'rank' => $leaderboard->id,
                               'reward' => $val[0],
                               'task_points' => $val[1]
                           ];
                       }
                   }
               }
            }
        }
        return $data;
    }
}
