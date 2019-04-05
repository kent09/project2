<?php

namespace App\Repository;

use App\Contracts\LeaderBoardInterface;
use App\Model\LeaderBoard;
use App\Model\ReferralLeaderBoardParticipant;
use App\Model\Referral;
use App\Model\TaskUser;
use App\Model\ReferralByLevel;
use App\ReferralTaskPoint;
use App\Model\ReferralReward;
use App\Model\VisitorCounter;
use App\Traits\Manager\UserTrait;
use App\Traits\UtilityTrait;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Model\LeaderBoardOwn;

class LeaderBoardRepository implements LeaderBoardInterface
{
    use UserTrait, UtilityTrait;
    const TASK_FIRST_LVL = 1;
    const TASK_SECOND_LVL = 0.5;
    const TASK_THIRD_LVL = 0.25;
    private $count = 0;
    public function referral($request)
    {
        $referrral_list = $this->getReferrals($request);
        if(count($referrral_list) > 0)
            return static::response(null,static::responseJwtEncoder($referrral_list), 201, 'success');
        return static::response('No Data Fetched', null, 200, 'success');
    }

    public function general($request){
        $general_list =  $this->getGeneralRange($request);
        if(count($general_list) > 0)
            return static::response(null,static::responseJwtEncoder($general_list), 201, 'success');
        return static::response('No Data Fetched', null, 200, 'success');
    }


    public function own($request){
        $own_list = $this->getOwnRange($request);
        if(count($own_list) > 0)
            return static::response(null,static::responseJwtEncoder($own_list), 201, 'success');
        return static::response('No Data Fetched', null, 200, 'success');
    }


    /**
     * @param $request
     *
     * @return array
     */
    public function getGeneralRange($request)
    {
        $range = $request->range;
        $filter_date = "";;
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        $list = [];
        $data = [];
        $leaderboard_earnings = 0;
        $column = 'weekly_top';
        $group_list = 'weekly_list';
        $ref_col = 'direct_level_signup_week';
        $limit = 10;
        if ($range == 'monthly') {
            $column = 'monthly_top';
            $group_list = 'monthly_list';
            $ref_col = 'direct_level_signup_month';
            $limit = 20;
        } elseif ($range == 'all-time') {
            $column = 'all_time_top';
            $group_list = 'all_time_list';
            $ref_col = 'direct_level_signup_alltime';
            $limit = 100;
        }

        $leaderboard_query = LeaderBoard::query();

        if ($filter_date != ""){
            $leaderboard_query = $leaderboard_query->whereDate('created_at','=',$filter_date);
        }

        $leaders = $leaderboard_query->limit($limit)->get([$column, 'updated_at', 'id']);
        if (count($leaders) > 0) {
            foreach ($leaders as $leader) {
                if ($leader->$column == null) {
                    break;
                }
                $top = json_decode($leader->$column);
                $profile = '';
                $name = '';
                $amount = '';
                $flag = '';
                $avatar = '';
                $userid = '';
                $referrals = '';
                $task_points = 0;
                $earned = 0;
                $lvl1 = 0;
                $lvl2 = 0;
                $lvl3 = 0;
                $dir_signup = 0;
                $referral_cnt = 0;
                foreach ($top as $key => $val) {
                    $userid = $key;
                    $profile = static::profile_link($key);
                    $user_info = static::get_user($key);
                    $flag = static::get_flag($key);
                    $amount = 0;
                    $participants = ReferralLeaderBoardParticipant::where('user_id', $key)->first();

                    if ($participants <> null) {
                        $participants_list = json_decode($participants->$group_list);
                        $referrals = count($participants_list);
                        if ($referrals > 0){
                            foreach ($participants_list as $item){
                                $task_points += $item->task_points;
                                $amount += $item->rewards;
                            }
                        }
                    }

                    $leaderboard = LeaderBoardOwn::where('user_id',$userid)->orderByDesc('created_at')->first();
                    if($leaderboard <> null){
                        if($leaderboard->referral_count <> '[]'){
                            $referral = json_decode($leaderboard->referral_count);
                            $referral_cnt = $referral->$ref_col;
                        }
                    }
                }
                $earned = $amount + $task_points;
                $leaderboard_earnings += $amount + $task_points;
                $is_follower = static::is_follower(['follower_id' => Auth::id(), 'followed_id' => $userid]);
                
            
                if ($earned > 0) {
                    $item = [
                        'rank' => $leader->id,
                        'userid' => $userid,
                        'reg_date' => date('Y-m-d',strtotime($user_info->created_at)),
                        'profile' => $profile,
                        'name' => $user_info->name,
                        'username' => $user_info->username,
                        'flag' => $flag,
                        'referrals' => $referral_cnt,
                        'rewards' => $amount,
                        'task_points' => $task_points,
                        'earned' => $earned,
                        'is_follower' => $is_follower
                    ];
                    array_push($data, $item);
                }
            }
        }

        $lb_updated_at = LeaderBoard::select(['updated_at'])->first();
        
        if(count($data) > 0){
            $list['list'] = $data;
            $list['leaderboard_updated_at'] = Carbon::parse($lb_updated_at->updated_at)->toDateTimeString();
            $list['leaderboard_earnings'] =  $leaderboard_earnings;

        }

        return $list;
    }


    /**
     * @param $request
     *
     * @return array
     */
    public function getOwnRange($request)
    {
        $range = $request->range;
        $user_id = Auth::id();

        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }

        $level = 0;
        $limit = 0;
        $filter_date = "";

        if($request->has('level')){
            $level = $request->level;
        }
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        if($request->has('limit')) {
            $limit = $request->limit;
        } 

        $list = [];
        $data = [];
        $total_earnings = 0;
        $leaderboard_earnings = 0;


        $column = 'all_time_top';
        $group_list = 'all_time_list';

        if ($range == 'monthly') {
            $column = 'monthly_top';
            $group_list = 'monthly_list';
        } elseif ($range == 'weekly'){
            $column = 'weekly_top';
            $group_list = 'weekly_list';
        }

        if($range == 'all-time')
            $limit = 0;
        if($range == 'monthly')
            $limit = 20;
        if($range == 'weekly')
            $limit = 10;

        // $dir_referrals = (new LeaderBoardOwn())->getDirectReferrals($user_id);
        $dir_referrals = LeaderBoardOwn::where('user_id',$user_id)->orderByDesc('created_at')->first();
        $dir_referralss = [];
        if($dir_referrals <> null){
            $dir_referralss = json_decode($dir_referrals->direct_referral_list);
        }
        
        $leaderboard = LeaderBoardOwn::where('user_id',$user_id)->orderByDesc('created_at')->first();
        $earned = 0;
        $user_rank = "";
        $this->count = 0;
        if($leaderboard <> null && is_object($leaderboard)){
            $top = json_decode($leaderboard->$group_list);
            foreach($top as $key => $val){
                $flag = static::get_flag($val->user_id);
                $is_follower = static::is_follower(['follower_id' => Auth::id(), 'followed_id' => $val->user_id]);
                $earned = $val->task_points + $val->rewards;
                $user_info = static::get_user($val->user_id);
                $item = [
                    'rank' => $val->rank,
                    'user_id' => $val->user_id,
                    'reg_date' => date('Y-m-d',strtotime($user_info->created_at)),
                    'name' => $user_info->name,
                    'username' => $user_info->username,
                    'flag' => $flag,
                    'status' => $user_info->status(false),
                    'task_points' => $val->task_points,
                    'rewards' => $val->rewards,
                    'earned' => $earned,
                    'direct_signup' => $val->ref_count_first,
                    'is_follower' => $is_follower,
                ];

                $key_search = true;
                if(is_array($dir_referralss) && count($dir_referralss) > 0){
                    $key_search = array_search($val->user_id, array_column($dir_referralss,'user_id'));
                }
               
                if($key_search <> false){
                    if($limit == 0){
                        if($filter_date <> ''){
                            if($filter_date == (date('Y-m-d',strtotime($user_info->created_at)))){
                                array_push($data, $item);
                                $this->count++;
                            }
                        }else{
                            array_push($data, $item);
                        }
                    }else{
                        if($filter_date <> ''){
                            if($filter_date == (date('Y-m-d',strtotime($user_info->created_at))) && $this->count < $limit){
                                array_push($data, $item);
                                $this->count++;
                            }
                        }else{
                            if($this->count < $limit){
                                array_push($data, $item);
                                $this->count++;
                            }   
                        }
                    }
                    
                    $leaderboard_earnings += $earned;
                }
            }

            $user_rank = $leaderboard->$range.'_rank';
        }

        $lb_info = static::get_user($user_id);
        if($range == 'all-time'){
            $range = 'all_time';
        }
        
        $user_referral = Referral::where('user_id',$user_id)->first();
        $user_referral_name = "";
        if($user_referral <> null){
            $user_referral_name = static::get_user($user_referral->referrer_id)->name;
        }
        $list['list'] = $data;
        $list['total_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_userid'] = $user_id;
        $list['leaderboard_name'] = $lb_info->name;
        $list['leaderboard_username'] = '@'.$lb_info->username;
        $list['leaderboard_rank'] = $user_rank;
        $list['referrer_name'] = $user_referral_name;

        return $list;

    }


    /**
     * @param $request
     *
     * @return array
     */
    public function getReferralRange($request)
    {
       
        $user_id = Auth::id();

        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }
        
        $level = 1;
        if($request->has('level')){
            $level = $request->level;
        }

        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $limit = 10;
        if($request->has('limit')) {
            $limit = $request->limit;
        }

       
        $total_earnings = 0;
        $leaderboard_earnings = 0;


        if($level == '2'){
            $col_level = 'second_referral_list';
        }elseif($level == '3'){
            $col_level = 'third_referral_list';
        }else{
            $col_level = 'direct_referral_list';
        }


        $leaderboard = LeaderBoardOwn::where('user_id',$user_id)->orderByDesc('created_at')->first();

        $earned = 0;
        $this->count = 0;
        $list = [];
        $data = [];
        if($leaderboard <> null){
            $top = json_decode($leaderboard->$col_level);
            if($top){
                foreach($top as $key => $val){
                    $flag = static::get_flag($val->user_id);
                    $is_follower = static::is_follower(['follower_id' => Auth::id(), 'followed_id' => $val->user_id]);
                    $earned = $val->task_points + $val->rewards;
                    $user_info = static::get_user($val->user_id);
                    $referrer = '';
                    $referral_dt = '';
                    $referrer_id = '';
                    $referrer_uname = '';
                    if($val->level <> '1st'){
                        $referral_info = Referral::where('user_id',$val->user_id)->first();
                        if($referral_info <> null){
                            $referrer_id = $referral_info->referrer_id;
                            $referrer = static::get_user($referrer_id)->name;
                            $referrer_uname = static::get_user($referrer_id)->username;
                            $referral_dt = Carbon::parse($referral_info->created_at)->toDateString();
                        }
                    }

                    $item = [
                        'level' => $val->level,
                        'user_id' => $val->user_id,
                        'reg_date' => date('Y-m-d',strtotime($val->reg_date)),
                        'reg_date_time' => $val->reg_date,
                        'name' => $user_info->name,
                        'username' => $user_info->username,
                        'flag' => $flag,
                        'status' => $user_info->status(false),
                        'task_points' => $val->task_points,
                        'rewards' => $val->rewards,
                        'earned' => $earned,
                        'referral_cnt' => $val->ref_cnt,
                        'is_follower' => $is_follower,
                        'referrer_id' => $referrer_id,
                        'referrer'=> $referrer,
                        'referrer_uname' => $referrer_uname,
                        'referral_dt' => $referral_dt,
                    ];
                    
                    if($filter_date <> ''){
                        if($filter_date == (date('Y-m-d',strtotime($val->reg_date))) && $this->count < $limit){
                            array_push($data, $item);
                            $this->count++;
                        }
                    }else{
                        if($this->count < $limit){
                            array_push($data, $item);
                            $this->count++;
                        }   
                    }
                   
                    $leaderboard_earnings += $earned;
                }
            }
        }


        $user_info =  static::get_user($user_id);
        $user_referral = Referral::where('user_id',$user_id)->first();
        $user_referral_name = "";
        if($user_referral != null){
            $user_referral_name = static::get_user($user_referral->referrer_id)->name;
           
        }
        $list['list'] = $data;
        $list['count'] = count($data);
        $list['referrer_name'] = $user_referral_name;
        $list['total_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_userid'] = $user_id;
        $list['leaderboard_name'] = $user_info->name;
        $list['leaderboard_username'] = '@'.$user_info->username;    
        
        return $list;
        
    }

    public function getReferrals($request)
    {
        $user_id = Auth::id();

        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }
        
        $level = 1;
        if($request->has('level')){
            $level = $request->level;
        }

        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $limit = 10;
        if($request->has('limit')) {
            $limit = $request->limit;
        }

        $offset = 0;
        if($request->has('offset')) {
            $offset = $request->offset;
        }

       
        $total_earnings = 0;
        $leaderboard_earnings = 0;

        $referrals = ReferralByLevel::where('user_id', Auth::id())->where('level',$level)->offset($offset)->limit($limit)->orderBy('referral_dt', 'DESC')->get();
        $count_referral = ReferralByLevel::where('user_id', Auth::id())->where('level',$level)->count();
        $earned = 0;
        $this->count = 0;
        $list = [];
        $data = [];
        $referrer = '';
        $referral_dt = '';
        $referrer_id = '';
        $referrer_uname = '';
        if (count($referrals) > 0) {
            foreach($referrals as $referral){
                $flag = static::get_flag($referral->referral_id);
                $is_follower = static::is_follower(['follower_id' => Auth::id(), 'followed_id' => $referral->referral_id]);
                $task_points = ReferralTaskPoint::where('user_id',$referral->user_id)->where('referral_id',$referral->referral_id)->sum('points');
                $rewards = ReferralReward::where('user_id',$referral->user_id)->where('referral_id',$referral->referral_id)->sum('reward');
                $earned = $task_points + $rewards;
                $user_info = static::get_user($referral->referral_id);
                $referral_cnt = ReferralByLevel::where('user_id', $referral->referral_id)->count();

                $referral_info = Referral::where('user_id',$referral->referral_id)->first();
                if($level <> 1){
                    if($referral_info <> null){
                        $referrer_id = $referral_info->referrer_id;
                        $referrer = static::get_user($referrer_id)->name;
                        $referrer_uname = static::get_user($referrer_id)->username;
                        $referral_dt = Carbon::parse($referral_info->created_at)->toDateString();
                        $task_points = ReferralTaskPoint::where('user_id',$referrer_id)->where('referral_id',$referral->referral_id)->sum('points');
                        $rewards = ReferralReward::where('user_id',$referrer_id)->where('referral_id',$referral->referral_id)->sum('reward');
                        $earned = $task_points + $rewards;
                    }
                }
                

                $item = [
                    'level' => $level,
                    'user_id' => $referral->referral_id,
                    'reg_date' => date('Y-m-d',strtotime($referral->referral->created_at)),
                    'reg_date_time' => $referral->referral->created_at,
                    'id' => $user_info->id,
                    'name' => $user_info->name,
                    'username' => $user_info->username,
                    'flag' => $flag,
                    'status' => $user_info->status(false),
                    'task_points' => $task_points,
                    'rewards' => $rewards,
                    'earned' => $earned,
                    'referral_cnt' => $referral_cnt,
                    'is_follower' => $is_follower,
                    'referrer_id' => $referrer_id,
                    'referrer'=> $referrer,
                    'referrer_uname' => $referrer_uname,
                    'referral_dt' => $referral_dt,
                ];
                
                array_push($data, $item);
                // if($filter_date <> ''){
                //     if($filter_date == (date('Y-m-d',strtotime($val->reg_date))) && $this->count < $limit){
                //         array_push($data, $item);
                //         $this->count++;
                //     }
                // }else{
                //     if($this->count < $limit){
                //         array_push($data, $item);
                //         $this->count++;
                //     }   
                // }
               
                $leaderboard_earnings += $earned;
            }
        }


        $user_info =  static::get_user($user_id);
        $user_referral = Referral::where('user_id',$user_id)->first();
        $user_referral_name = "";
        if($user_referral != null){
            $user_referral_name = static::get_user($user_referral->referrer_id)->name;
        }
        $list['list'] = $data;
        $list['count'] = $count_referral;
        $list['referrer_name'] = $user_referral_name;
        $list['total_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_earnings'] =  $leaderboard_earnings;
        $list['leaderboard_userid'] = $user_id;
        $list['leaderboard_name'] = $user_info->name;
        $list['leaderboard_username'] = '@'.$user_info->username;    
        
        return $list;
    }

}