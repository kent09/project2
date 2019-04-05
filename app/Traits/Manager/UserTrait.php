<?php

namespace App\Traits\Manager;

use App\User;
use App\Helpers\UtilHelper;
use App\Model\UserCookie;
use App\Model\TaskUser;
use App\Model\Balance;
use App\Model\Referral;
use App\Model\SocialMedia;
use App\Model\UserFollower;
use App\Model\UserActivity;
use App\Model\BlockedUser;
use App\Model\SocialConnect;
use App\Model\SocialConnectHistory;
use App\Model\SocialConnectStatus;
use App\Model\VisitorCounter;
use App\Model\BannedUserTask;
use App\Model\UserReputationActivityScore;
use App\Model\ReferralLeaderBoardParticipant;
use App\Model\Blog;
use App\Model\LeaderBoardOwn;
use App\Model\UserFreeTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use LaravelHashids\Facades\Hashids;
use Firebase\JWT\JWT;
use Carbon\Carbon;
use App\Mail\ActivationEmail;
use Illuminate\Support\Facades\DB;
trait UserTrait
{
    private $user;
    private $total;

    public function __construct()
    {
        $this->user = null;
        $this->total = 0;
    }

    public function all()
    {
        $total = User::count();
        $users = User::orderbyDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function active()
    {
        $total = User::where('verified', 1)->where('status', 1)->where('agreed', 1)->where('ban', 0)->count();
        $users = User::where('verified', 1)->where('status', 1)->where('agreed', 1)->where('ban', 0)->orderByDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function unverified()
    {
        $total = User::where('verified', 0)->where('ban', 0)->count();
        $users = User::where('verified', 0)->where('ban', 0)->orderByDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function unconfirmed()
    {
        $total = User::where('verified', 1)->where(function ($q) {
            $q->where('status', 0)->orWhere('agreed', 0);
        })->where('request_confirmation_at' , null)->where('ban', 0)->count();
        $users = User::where('verified', 1)->where(function ($q) {
            $q->where('status', 0)->orWhere('agreed', 0);
        })->where('request_confirmation_at' , null)->where('ban', 0)->orderByDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function soft_banned()
    {
        $total = User::where('ban', 1)->count();
        $users = User::where('ban', 1)->orderByDesc('ban_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function hard_banned()
    {
        $total = User::where('ban', 2)->count();
        $users = User::where('ban', 2)->orderByDesc('ban_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }
    
    public function disabled()
    {
        $total = User::where('verified', 1)->where('status', 0)->where('agreed', 1)->where('ban', 0)->count();
        $users = User::where('verified', 1)->where('status', 0)->where('agreed', 1)->where('ban', 0)->orderByDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function banned_today()
    {
        $now = Carbon::now()->toDateString();
        $total = User::where('ban', '>', 0)->whereDate('ban_at',$now)->count();
        $users = User::where('ban', '>', 0)->whereDate('ban_at',$now)->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function get_ban_status($status_id)
    {
       $status = 'soft-banned';
       if($status_id == '2'){
            $status = 'hard-banned';
       }

       return $status;
    }

    public function all_banned()
    {
        $total = User::where('ban', '>', 0)->count();
        $users = User::where('ban', '>', 0)->orderByDesc('ban_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function daily_visitors(){
        $now = Carbon::now()->toDateString();
        $total = VisitorCounter::whereDate('created_at',$now)->count();
        $users = VisitorCounter::whereDate('created_at',$now)->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function device_user_count(){
        $mobile = VisitorCounter::where('device','mobile')->count();
        $desktop = VisitorCounter::where('device','desktop')->count();
        $tablet = VisitorCounter::where('device','tablet')->count();

        return compact('mobile','desktop','tablet');
    }


    public function requested_manual_confirmation()
    {
        $total = User::where('verified', 1)->where('status', 0)->where('agreed', 0)->where('request_confirmation_at', '<>', null)->where('ban', 0)->count();
        $users = User::where('verified', 1)->where('status', 0)->where('agreed', 0)->where('request_confirmation_at', '<>', null)->where('ban', 0)->orderByDesc('created_at')->get();

        $this->total = $total;
        $this->users = $users;
        return $this;
    }

    public function get_latest_used_ip($user_id=0, $with_country=false){
        $user = VisitorCounter::where('user_id',$user_id)->orderByDesc('created_at')->first();
        
        if($with_country){
            return $user->ip ." - ". $user->iso;
        }
        return $user->ip;
    }


    public function newly_registered(){
        $now = Carbon::now()->toDateString();

        $this->total = User::whereDate('created_at',$now)->count();
        $this->users = User::whereDate('created_at',$now)->orderByDesc('created_at')->get();
        return $this;
    }

     /**
     * create new user if not record yet
     *
     * @param array $data [all columns in user]
     * @return array
     */
    public function create_user($data)
    {
        $data = to_obj($data);

        $user = User::where('email', $data->email)->first();
        if ($user != null) {
            return return_data("Email is already used", 422);
        }
        $user = User::where('username', $data->username)->first();
        if ($user != null) {
            return return_data("Username is already used", 422);
        }

        $user = new User;
        $user->name = $data->name;
        $user->email = $data->email;
        $user->username = snake_case($data->username);
        $user->password = $data->password;
        if (isset($data->verified)) {
            $user->verified = $data->verified;
        }
        if (isset($data->email_token)) {
            $user->email_token = $data->email_token;
        }
        if (isset($data->status)) {
            $user->status = $data->status;
        }
        if (isset($data->ip)) {
            $user->ip = $data->ip;
        }
        if (isset($data->balance)) {
            $user->balance = $data->balance;
        }
        if (isset($data->agreed)) {
            $user->agreed = $data->agreed;
        }
        if (isset($data->about)) {
            $user->about = $data->about;
        }
        if (isset($data->location)) {
            $user->location = $data->location;
        }
        if (isset($data->country)) {
            $user->country = $data->country;
        }
        if (isset($data->has_avatar)) {
            $user->has_avatar = $data->has_avatar;
        }
        if (isset($data->referrer_id)) {
            $user->referrer_id = $data->referrer_id;
        }

        $user->ref_code = static::get_referral_code($user->id);
        $user->save();

        return return_data($user, 201);
    }

    /**
     * @param $user_id
     *
     * @return string
     */
    public function profile_link($user_id)
    {
        $user = User::find($user_id);
        if ($user==null){
            return '';
        }
        if ($user->username != null OR $user->username != ''){
            return '/p/'.$user->username;
        }
        return '/p/' . $user->id;
    }

    /**
     * @param $user_id
     *
     * @return string
     */
    public function get_user($user_id=0)
    {
        if ($user_id == 0) {
            $user_id = Auth::id();
        }
        $user = User::find($user_id);
        return $user;
    }

    /**
     * @param $user_id
     *
     * @return string
     */
    public function get_referral_code($user_id)
    {
        $user_id = $user_id ?? Auth::id();

        $code = Hashids::encode($user_id);

        $found = User::where('id', $user_id)
                        ->where(function($q){
                            $q->where('ref_code', '')
                            ->orWhere('ref_code', null);
                        })
                        ->count();

        if ($found > 0){
            $user = User::find($user_id);
            $user->ref_code = $code;
            $user->save();
        }

        return $code;
    }


    /**
     * @param $user_id
     *
     * @return string
     */
    public function get_flag($user_id)
    {
        $user = User::find($user_id);
        if ($user == null){
            return '';
        }
        if ($user->country == null || $user->country == ''){
            $visitor = VisitorCounter::where('user_id', $user_id)->orderBy('id', 'desc')->first();
            if ($visitor != null){
                if ($visitor->iso != null || $visitor->iso != ''){
                    return strtolower($visitor->iso);
                }
            }
        }

        return '';
    }

    /**
     * @param $user_id
     *
     * @return string
     */
    public function get_avatar($user_id){
        $avatar = 'https://kimg.io/image/user-avatar-default.png';
        if (@GetImageSize('https://kimg.io/image/profiles/'.$user_id.'/avatar.png')){
            $avatar = 'https://kimg.io/image/profiles/'.$user_id.'/avatar.png';
        }
        return $avatar;
    }

    /**
     * update user basic info
     *
     * @param array $data ['id', 'name', 'username', 'about', 'location', 'country']
     * @return array
     */
    public function update_basic($data)
    {

        $data = to_obj($data);

        if (isset($data->id)) {
            $user_id = $data->id;
        } else {
            $user_id = Auth::id();
        }
        $user = User::find($user_id);
        if ($user != null) {
            if (isset($data->name)) {
                $user->name = $data->name;
            }
            if (isset($data->username)) {
                $user->username = snake_case($data->username);
            }
            if (isset($data->about)) {
                $user->about = $data->about;
            }
            if (isset($data->location)) {
                $user->location = $data->location;
            }
            if (isset($data->country)) {
                $user->country = $data->country;
            }
            if($user->save()){
                return return_data($user);
            }
        }

        return return_data($user, 400);
    }

     /**
     * update own password
     *
     * @param string $new_password
     * @param int $user_id
     * @return array
     */
    public function update_password($new_password, $user_id = null)
    {
        $user = User::find($user_id);

        if ($user != null) {
            $user->password = b_crypt($new_password);
            $user->save();

            return return_data($user);
        }
        return return_data($user, 400);
    }


     /**
     * check if passwords are the same
     *
     * @param string $raw_password
     * @param int $user_id
     * @return boolean
     */
    public function is_same_password($raw_password, $user_id = null)
    {

        $user = User::find($user_id);
        if ($user == null) {
            return false;
        }

        if (Hash::check($raw_password, $user->password)) {
            return true;
        } else {
            return false;
        }
    }

     /**
     * get list of followers
     *
     * @param array $data ['user_id', 'paginate']
     * @return array
     */
    public function get_follower_list($data = [])
    {
        $data = to_obj($data);
        $list = [];

        $blocked = BlockedUser::where('blocker_id', $data->user_id) ->where('status',1)->pluck('blocked_id');
        $followers_query = UserFollower::where('user_id', $data->user_id)
                                        ->join('users', 'users.id', '=', 'user_followers.follower_id')
                                        ->where('user_followers.status', '<>', 0)
                                        ->whereNotIn('user_followers.follower_id',$blocked);

        
        if($data->search_key <> ''){
            $followers_query = $followers_query->where('users.name', 'LIKE', '%'.$data->search_key.'%');
        }

        $count = count($followers_query->get()->toArray());
        $followers = $followers_query->skip($data->offset)->take($data->limit)->orderBy('users.name','asc')->get();
        $list['data'] = $followers;
        $list['count'] = $count;

        return $list;
    }

     /**
     * count user followers
     *
     * @param string $data ['user_id']
     * @return array
     */
    public function count_followers($user_id)
    {
        $follower = UserFollower::where('user_id', $user_id)->where('status', 1)->get();
        return $follower->count();
    }



     /**
     * get list of following
     *
     * @param array $data ['user_id', 'paginate']
     * @return array
     */
    public function get_following_list($data = [])
    {
        $data = to_obj($data);
        $list = [];

        $following_query = UserFollower::where('follower_id', $data->user_id)
                                        ->join('users', 'users.id', '=', 'user_followers.user_id')
                                        ->where('user_followers.status', '<>', 0);

        if($data->search_key <> ''){
            $following_query = $following_query->where('users.name', 'LIKE', '%'.$data->search_key.'%');
        }

        $count = count($following_query->get()->toArray());
        $following = $following_query->skip($data->offset)->take($data->limit)->orderBy('users.name','asc')->get();

        $list['data'] = $following;
        $list['count'] = $count;

        return $list;
    }

     /**
     * count user following
     *
     * @param string $user_id
     * @return array
     */
    public function count_following($user_id)
    {
        $following = UserFollower::where('follower_id', $user_id)->where('status', 1)->get();
        return $following->count();
    }


     /**
     * get list of connections
     *
     * @param array $data ['user_id', 'paginate']
     * @return array
     */
    public function get_connections_list($data = [])
    {
        $data = to_obj($data);
        $connection_list = UserFollower::getConnections($data->user_id);
        $list = [];
        $users = [];
        $count = 0;

        if(count($connection_list) > 0){
            $connections_query = User::whereIn('id',$connection_list);

            if($data->search_key <> ''){
                $connections_query = $connections_query->where('name', 'LIKE', '%'.$data->search_key.'%');
            }
            
            $count = count($connections_query->get()->toArray());
            $users = $connections_query->skip($data->offset)->take($data->limit)->orderBy('name','asc')->get();            
        }
        
        $list['data'] = $users;
        $list['count'] = $count;

        return $list;
    }


    /**
     * count user connections
     *
     * @param string $user_id
     * @return array
     */
    public function count_connections($user_id)
    {
        $connection_list = UserFollower::getConnections($user_id);
        return count($connection_list);
    }

    
    public function soft_unlinked($social=''){
        if($social == ''){
            $users = SocialConnect::where('status',2)->get();
        }else{
            $users = SocialConnect::where('status',2)->where('social',$social)->get();
        }

        $total = count($users);
        $this->total = $total;
        $this->users = $users;
        return $this;  
    }

    public function hard_unlink_request($social=''){
        if($social == ''){
            $users = SocialConnect::where('hard_unlink_status',SocialConnect::hu_status_requested)->get();
        }else{
            $users = SocialConnect::where('hard_unlink_status',SocialConnect::hu_status_requested)->where('social',$social)->get();
        }

        $total = count($users);
        $this->total = $total;
        $this->users = $users;
        return $this;  
    }

    function new_social_connected($social=''){
        $now = Carbon::now()->toDateString();
        if($social == ''){
            $users = SocialConnect::where('status',1)->whereDate('created_at',$now)->get();
        }else{
            $users = SocialConnect::where('status',1)->whereDate('created_at',$now)->where('social',$social)->get();
        }

        $total = count($users);
        $this->total = $total;
        $this->users = $users;
        return $this;  
    }


     /**
     * get list of blocked users
     *
     * @param array $data ['user_id', 'paginate']
     * @return array
     */
    public function get_blocked_users_list($data = [])
    {
        $data = to_obj($data);
        $user_id = $data->user_id;
        $paginate = $data->paginate;

        if($data->paginate){
            $blocked_list = BlockedUser::where('blocker_id',$user_id)
                                        ->leftJoin('users', 'users.id', '=', 'blocked_users.blocked_id')                
                                        ->paginate($data->paginate);
        }else{
            $blocked_list = BlockedUser::where('blocker_id',$user_id)
                                        ->leftJoin('users', 'users.id', '=', 'blocked_users.blocked_id')
                                        ->get();
        }

        if(count($blocked_list) > 0){
            return return_data($blocked_list);
        }

        return return_data($users,400);
    }


     /**
     * get timeline
     *
     * @param array $data ['user_id', 'paginate', 'sort']
     * @return array
     */
    public function get_timeline($data = [])
    {
        $data = to_obj($data);
        $user_id = $data->user_id;
        $limit = $data->limit;
        $offset = $data->offset;
        $sort = $data->sort;

        $time_line = User::with([
            'completer' => function ($q) {
                $q->select(
                    'tasks.category',
                    'tasks.title',
                    'tasks.slug',
                    'task_user.user_id',
                    'users.username',
                    'task_user.task_id',
                    'task_user.task_creator',
                    'task_user.created_at',
                    'task_user.status'
                )->leftJoin('tasks','tasks.id','=','task_user.task_id')
                 ->leftJoin('users','users.id','=','task_user.user_id')->where('revoke', 0)->get();
            },
            'followers' => function ($q) {
                $q->select('user_followers.follower_id', 'user_followers.user_id', 'users.username', 'user_followers.status', 'user_followers.created_at')
                  ->leftJoin('users','users.id','=','user_followers.follower_id')->get();
            },
            'giftReceiver' => function ($q) {
                $q->select(
                    'receiver_id',
                    'sender_id',
                    'coin',
                    'gift_coin_transactions.created_at',
                    'users.name',
                    'users.username',
                    'user_r.name as receiver_name',
                    'user_r.username as receiver_username'
                )->leftJoin('users','users.id','=','sender_id')->where('active', 1)
                 ->leftJoin('users as user_r','user_r.id','=','receiver_id')->where('active', 1)->get();
            },
            'giftSender' => function ($q) {
                $q->select(
                    'receiver_id',
                    'sender_id',
                    'coin',
                    'gift_coin_transactions.created_at',
                    'users.name',
                    'users.username',
                    'user_s.name as sender_name',
                    'user_s.username as sender_username'
                )->leftJoin('users','users.id','=','receiver_id')->where('active', 1)
                 ->leftJoin('users as user_s','user_s.id','=','sender_id')->where('active', 1)->get();
            },
            'revokeTask' => function ($q) {
                $q->select(
                    'tasks.category',
                    'tasks.title',
                    'tasks.slug',
                    'task_user.user_id',
                    'users.username',
                    'task_user.task_id',
                    'task_user.task_creator',
                    'task_user.created_at',
                    'task_user.status'
                )->leftJoin('tasks','tasks.id','=','task_user.task_id')
                 ->leftJoin('users','users.id','=','task_user.user_id')->where('revoke', 1)->get();
            }
        ])->where('id', $user_id)->first();

        $t_data = [];
        collect($time_line)->each(function ($item, $key) use (&$t_data) {
            if ($item) {
                if (is_array($item)) {
                    if ($key === 'completer') {
                        $arr_completer = [];
                        foreach ($item as $k => $i) {
                            if (is_array($i)) {
                                foreach ($i as $i_a3 => $i_3) {
                                    $arr_completer[$k][$i_a3] = $i_3;
                                    $arr_completer[$k]['type'] = 'completed';
                                    $dt = new Carbon($i['created_at']);
                                    $arr_completer[$k]['timeago'] = $dt->diffForHumans();
                                }
                            }
                        }
                        $t_data['transaction'][] = $arr_completer;
                    }

                    if ($key === 'followers') {
                        $arr_follower = [];
                        foreach ($item as $k => $i) {
                            if (is_array($i)) {
                                foreach ($i as $i_a3 => $i_3) {
                                    $arr_follower[$k][$i_a3] = $i_3;
                                    $arr_follower[$k]['type'] = 'follow';
                                    $dt = new Carbon($i['created_at']);
                                    $arr_follower[$k]['timeago'] = $dt->diffForHumans();
                                }
                            }
                        }
                        $t_data['transaction'][] = $arr_follower;
                    }

                    if ($key === 'gift_receiver') {
                        $arr_gift = [];
                        foreach ($item as $k => $i) {
                            if (is_array($i)) {
                                foreach ($i as $i_a3 => $i_3) {
                                    $arr_gift[$k][$i_a3] = $i_3;
                                    $arr_gift[$k]['type'] = 'gift-coin';
                                    $dt = new Carbon($i['created_at']);
                                    $arr_gift[$k]['timeago'] = $dt->diffForHumans();

                                }
                            }
                        }
                        $t_data['transaction'][] = $arr_gift;
                    }

                    if ($key === 'gift_sender') {
                        $arr_gift_sender = [];
                        foreach ($item as $k => $i) {
                            if (is_array($i)) {
                                foreach ($i as $i_a3 => $i_3) {
                                    $arr_gift_sender[$k][$i_a3] = $i_3;
                                    $arr_gift_sender[$k]['type'] = 'gift-coin-sender';
                                    $dt = new Carbon($i['created_at']);
                                    $arr_gift_sender[$k]['timeago'] = $dt->diffForHumans();

                                }
                            }
                        }
                        $t_data['transaction'][] = $arr_gift_sender;
                    }

                    if ($key === 'revoke_task') {
                        $arr_revoked = [];
                        foreach ($item as $k => $i) {
                            if (is_array($i)) {
                                foreach ($i as $i_a3 => $i_3) {
                                    $arr_revoked[$k][$i_a3] = $i_3;
                                    $arr_revoked[$k]['type'] = 'revoke';
                                    $dt = new Carbon($i['created_at']);
                                    $arr_revoked[$k]['timeago'] = $dt->diffForHumans();

                                }
                            }
                        }
                        $t_data['transaction'][] = $arr_revoked;
                    }
                }
            }
        });


        $arr_data = [];
        $t_data = collect($t_data);
        $t_data->each(function ($item) use (&$arr_data) {
            if (is_array($item)) {
                foreach ($item as $k => $x) {
                    foreach ($x as $k_1 => $j) {
                        $arr_data[] = $j;
                    }
                }
            }
        });


        $time_line_data = null;
        $arr_data = collect($arr_data);
        if ($sort == 'desc') {
            $time_line_data = $arr_data->sortByDesc('created_at')->groupBy(function ($item) {
                return date('Y-m-d', strtotime($item['created_at']));
            });
        } else {
            $time_line_data = $arr_data->sortBy('created_at',null,false)->groupBy(function ($item) {
                return date('Y-m-d', strtotime($item['created_at']));
            });
        }

        $list['count'] = count($time_line_data);

        # LIMIT TRANSACTIONS PER TIMELINE DATE
        foreach($time_line_data as $key => $value){
            $time_line_data[$key] = collect($time_line_data[$key])->forPage($offset,$limit);  
        }
        $time_line_data = collect($time_line_data)->forPage($offset,$limit);
        $list['data'] = $time_line_data;

        return $list;
    }
     /**
     * get user activity list
     *
     * @param array $data ['user_id', 'limit']
     * @return array
     */
    public function get_user_activity_list($data = [])
    {

        $data = to_obj($data);

        $list = UserActivity::where('user_id', $data->user_id)->offset($data->offset)->limit($data->limit)->get();

        if (count($list) > 0) {
            return return_data($list);
        } else {
            return return_data(null, 400);
        }
    }

    /**
     * create entry if not yet existed
     *
     * @param array $data ['user_id', 'passport_id', 'selfie']
     * @return array
     */
    public function create_verified_entry($data = [])
    {
        $data = to_obj($data);
        $entry = Verified::where('user_id', $data->user_id)->first();
        if ($entry != null) {
            return return_data("User record is already existed", 422);
        }
        $entry = Verified::where('passport_id', $data->passport_id)->first();
        if ($entry != null) {
            return return_data("Passport record is already existed", 422);
        }
        if (!isset($data->selfie) || $data->selfie == '') {
            return return_data("Selfie is required", 422);
        }
        $entry = new Verified;
        $entry->user_id = $data->user_id;
        $entry->passport_id = $data->passport_id;
        $entry->selfie = $data->selfie;
        $entry->status = 0;
        $entry->save();

        return return_data($entry, 201);
    }

      /**
     * get user reputation score
     *
     * @param string $data ['user_id']
     * @return array
     */
    public function get_reputation_score($user_id)
    {
        $revokes =  BannedUserTask::where('user_id', $user_id)->count();
        $activity_score = UserReputationActivityScore::where('user_id', $user_id)->first();

        if($activity_score['activity_score'] == 0 && $revokes == 0) {
            return ['reputation' => null, 'activity_score' => null];
        }

        if($activity_score['activity_score'] == 0) {
            return ['reputation' => $activity_score['reputation'], 'activity_score' => 0];
        }

        if($revokes > 0 && $activity_score['activity_score'] != 0) {
            $reputation = @($revokes / $activity_score->activity_score) * 100 - 100;
            if($activity_score) {
                $activity_score->reputation = abs($reputation);
                $activity_score->save();
            }
            return ['reputation' => round(abs($reputation)), 'activity_score' => $activity_score['activity_score']];
        }

        return ['reputation' => $activity_score['reputation'], 'activity_score' => $activity_score['activity_score']];
    }

    /**
     * get linked social accounts
     *
     * @param string $data ['user_id']
     * @return array
     */
    function get_linked_social_connections($user_id){

        $social = SocialConnect::where('user_id',$user_id)
                               ->where('status',1)
                               ->get();

        if(count($social) > 0){
            return return_data($social);
        }

        return return_data($social, 400);
    }


    /**
     * get all social connections
     *
     * @param string $data ['user_id']
     * @return array
     */
    function get_all_social_connections($user_id){
        $social_medias = SocialMedia::where('is_active',1)->orderBy('id','asc')->get();
        $socials = [];

        if(count($social_medias) > 0){

            foreach($social_medias as $key => $val){
                $con_status = static::check_social_connection_status($user_id,$val->social);
                $item = [
                    'sc_id' => $con_status['sc_id'],
                    'social_media' => $val->social,
                    'status' => $con_status['status'],
                    'status_desc'=> $con_status['desc'],
                    'hard_unlink_reason' => $con_status['reason'],
                    'hard_unlink_status' => $con_status['hard_unlink_status']
                ];
                array_push($socials,$item);
            }
        }

        if(count($socials) > 0){
            return return_data($socials);
        }

        return return_data($socials, 400);
    }

    /**
     * check social connection status by social name
     *
     * @param string $data ['user_id',social']
     * @return array $status_arr
     */
    function check_social_connection_status($user_id,$social){
        $user_id  = $user_id ?? Auth::id();
        $status = "not yet";
        $status_desc = "";
        $reason = "";
        $sc_id = 0;
        $status_arr = [];
        $hardUnlinkStatus = "";
        if($social <> ''){
            $social_con = SocialConnect::where('user_id',$user_id)->where('social',$social)->first();

            if($social_con <> null){
                $connect_status = SocialConnectStatus::where('id',$social_con->status)->first();
                if($connect_status <> null){
                    $status = $connect_status->status;
                    $status_desc = $connect_status->description;
                    if($social_con->reason <> null){
                        $reason = json_encode($social_con->reason);
                    }
                    $sc_id = $social_con->id;
                    $hardUnlinkStatus =  $social_con->hard_unlink_status;
                }
            }
            $status_arr = [
                'sc_id' => $sc_id,
                'status' => $status,
                'desc' => $status_desc,
                'reason' => $reason,
                'hard_unlink_status' => $hardUnlinkStatus
            ];
        }

        return $status_arr;
    }


    /**
     * block user
     *
     * @param array $data ['blocker_id', 'blocked_id']
     * @return array
     */
    public function block_user($data = [])
    {
        $data = to_obj($data);
        $blocker_id = $data->blocker_id;
        $blocked_id = $data->blocked_id;

        $block = BlockedUser::where('blocked_id', $blocked_id)
                            ->where('blocker_id', $blocker_id)
                            ->get();

        if(count($block) > 0){
            $block = BlockedUser::where('blocked_id', $blocked_id)
                                ->where('blocker_id', $blocker_id)
                                ->update(['status' => 1]);

            if($block){
                return true;
            }
        }else{
            $block = new BlockedUser;
            $block->blocker_id = $blocker_id;
            $block->blocked_id = $blocked_id;
            $block->status = 1;
            if($block->save()){
                return true;
            }
        }

        $unfollow1 = UserFollower::where('user_id',$blocked_id)
                                 ->where('follower_id',$blocker_id)
                                 ->update(['status' => 0]);

        $unfollow2 = UserFollower::where('user_id', $blocker_id)
                                 ->where('follower_id', $blocked_id)
                                 ->update(['status' => 0]);

        return false;
    }

    
    public function is_blocked_user($id,$follower_id){
        $list = array();
        $blocked = BlockedUser::where('blocker_id', $follower_id) ->where('status',1)->get(['blocked_id']);
        foreach ($blocked as $value) {
            array_push($list, $value['blocked_id']);
        }
        if(in_array($id, $list)){
           return true;
        }else{
           return false;
        }

   }

     /**
     * unblock user
     *
     * @param array $data ['blocker_id', 'blocked_id']
     * @return array
     */
    public function unblock_user($data = [])
    {
        $data = to_obj($data);
        $blocker_id = $data->blocker_id;
        $blocked_id = $data->blocked_id;

        $block = BlockedUser::where('blocked_id', $blocked_id)
                            ->where('blocker_id', $blocker_id)
                            ->update(['status' => 0]);
        if($block){
            return true;
        }else{
            return false;
        }
    }

     /**
     * follow user
     *
     * @param array $data ['follower_id', 'followed_id']
     * @return array
     */
    public function follow_user($data = [])
    {
        $data = to_obj($data);
        $follower_id = $data->follower_id;
        $followed_id = $data->followed_id;

        $follower_info = $this->get_user($follower_id);

        $following = UserFollower::where('user_id',$followed_id)
                                 ->where('follower_id',$follower_id)
                                 ->get();

        if(count($following) == 0){
            $follow = new UserFollower;
            $follow->user_id = $followed_id;
            $follow->follower_id = $follower_id;
            $follow->status = 1;
            if($follow->save()){
               return true;
            }
        }else{
            $following = UserFollower::where('user_id',$followed_id)
                                     ->where('follower_id',$follower_id)
                                     ->update(['status' => 1]);

            if($following){
               return true;
            }
        }
        return false;
    }

    /**
     * unfollow user
     *
     * @param array $data ['follower_id', 'followed_id']
     * @return array
     */
    public function unfollow_user($data = [])
    {
        $data = to_obj($data);
        $follower_id = $data->follower_id;
        $followed_id = $data->followed_id;

        $unfollow = UserFollower::where('user_id',$followed_id)
                                ->where('follower_id',$follower_id)
                                ->update(['status' => 0]);

        if($unfollow){
           return true;
        }
        return false;
    }

     /**
     * check if user is a follower
     *
     * @param array $data ['follower_id', 'followed_id']
     * @return array
     */
    public function is_follower($data = []){

        $data = to_obj($data);
        $follower_id = $data->follower_id;
        $followed_id = $data->followed_id;

        $follower = UserFollower::where('follower_id', $follower_id)
                                ->where('user_id', $followed_id)
                                ->where('status',1)
                                ->get();

       return count($follower) > 0;
    }

     /**
     * check if user is following
     *
     * @param array $data ['follower_id', 'followed_id']
     * @return array
     */
    public function is_following($data = []){
        $data = to_obj($data);
        $follower_id = $data->follower_id;
        $followed_id = $data->followed_id;

        $following = UserFollower::where('follower_id', $followed_id)
                                 ->where('user_id',$follower_id)
                                 ->where('status',1)
                                 ->get();

        return count($following) > 0;
    }

    /**
     * link social media account
     *
     * @param array $data ['user_id', 'social']
     * @return array
     */
    public function link_social($data = []){
        $data = to_obj($data);
        $user_id = $data->user_id;
        $social = $data->social;

        $link = SocialConnect::where('user_id',$user_id)
                             ->where('social',$social)
                             ->get();

        if(count($link) > 0){
            $link = SocialConnect::where('user_id',$user_id)
                                 ->where('social',$social)
                                 ->update(['status' => 1]);
            if($link){
                static::save_social_connect_history($user_id,$social);
                return true;
            }
        }
        return false;
    }

    /**
     * unlink social media account
     *
     * @param array $data ['user_id', 'social']
     * @return array
     */
    public function unlink_social($data = []){
        $data = to_obj($data);
        $user_id = $data->user_id;
        $social = $data->social;

        $link = SocialConnect::where('user_id',$user_id)
                             ->where('social',$social)
                             ->update(['status' => 3]);

        if($link){
            static::save_social_connect_history($user_id,$social);
            return true;
        }

        return false;
    }


    /**
     * save social connect history
     *
     * @param array $data ['user_id', 'social']
     * @return array
     */
    public function save_social_connect_history($user_id,$social){
        $user_id = $user_id ?? Auth::id();

        $social_con = SocialConnect::where('user_id',$user_id)
                             ->where('social',$social)->first();

        if($social_con){
            $social = SocialMedia::where('social',$social_con->social)->first();
            $data = [
                'user_id' => $social_con->user_id,
                'social_id' => $social->id,
                'account_name' => $social_con->account_name,
                'account_id' => $social_con->account_id,
                'status' => $social_con->status
            ];
            $saveHistory = (new SocialConnectHistory())->saveData($data);
            return $saveHistory;
        }

        return false;
    }


    /**
     * get user referrals with task points by level
     *
     * @param array $data ['user_id','level', 'limit']
     * @return array
     */
    public function get_referrals_with_task_points($data){
        $data = to_obj($data);
        $user_id = $data->user_id ?? Auth::id();
        $level = $data->level;
        $limit = $data->limit ?? 10;
        $referrals = [];
       

        $col_level = 'direct_referral_list';

        if($level == '2'){
            $col_level = 'second_referral_list';
        }elseif($level == '3'){
            $col_level = 'third_referral_list';
        }

        $referral = LeaderBoardOwn::where('user_id',$user_id)->orderByDesc('created_at')->first();
        if($referral <> null){
            $referral = json_decode($referral->$col_level);
            if($referral){
                foreach($referral as $key => $value){
                    if($value->task_points <> 0){
                        $referrals[] = $value->user_id;
                    }
                }
            }
        }

        return $referrals;
    }

    /**
     * get user voting value
     *
     * @param int $user_id
     * @return int
     */
    public function get_user_voting_value($user_id){
        $user_id = $user_id ?? Auth::id();

        $voting_val = Balance::where('user_id',$user_id)->first();

        if($voting_val <> null){
            return $voting_val->total;
        }else{
            return 0;
        }
    }   

     /**
     * get user referral count
     *
     * @param int $user_id
     * @return int
     */
    public function get_user_referral_count($user_id){
        $user_id = $user_id ?? Auth::id();

        $referrals = Referral::where('referrer_id',$user_id)->get();
        
        return count($referrals);

    }   

    /**
     * create user cookie entry and soft-ban user if abuse in an hour
     *
     * @param array $data ['user_id', 'cookie', 'on_reg']
     * @return array
     */
    public function create_cookie($data)
    {
        $data = to_obj($data);
        if ($data->on_reg == 1) {
            $latest = UserCookie::where('cookie', $data->cookie)->where('on_reg', 1)->orderBy('updated_at', 'desc')->first();
            if ($latest != null) {
                $latest_date = Carbon::createFromFormat('Y-m-d H:i:s', $latest->updated_at);
                $now = Carbon::now();
                $minutes = $now->diffInMinutes($latest_date);
                if ($minutes <= 60) {
                    UserService()->soft_ban($data->user_id);
                    UserService()->soft_ban($latest->user_id);
                }
            }
        }
        $user_cookie = UserCookie::where('user_id', $data->user_id)->where('cookie', $data->cookie)->first();
        if ($user_cookie == null) {
            $user_cookie = new UserCookie;
            $user_cookie->user_id = $data->user_id;
            $user_cookie->cookie = $data->cookie;
            $user_cookie->on_reg = $data->on_reg;
            $user_cookie->status = 1;
            $user_cookie->save();
            
            return return_data($user_cookie, 201);
        } else {
            $user_cookie->user_id = $data->user_id;
            $user_cookie->cookie = $data->cookie;
            $user_cookie->on_reg = $data->on_reg;
            $user_cookie->status = 1;
            $user_cookie->save();
            
            return return_data($user_cookie);
        }
    }


    /**
     * get referrer info by referral code
     *
     * @param string $ref_code
     * @return array
     */
    public function referrer_by_ref_code($ref_code)
    {
        $user = User::where('ref_code', $ref_code)->where('status', 1)->where('verified', 1)->where('agreed', 1)->where('ban', '<>', 2)->first();
        return $user;
    }

    /**
     * create referral entry if not yet existing return object if existed
     *
     * @param array $data ['user_id', 'referrer_id']
     * @return array
     */
    public function create_referral($data)
    {
        $data = to_obj($data);
        if (isset($data->user_id)) {
            $user_id = $data->user_id;
        } else {
            $user_id = Auth::id();
        }
        if (isset($data->referrer_id)) {
            if ($data->user_id == $data->referrer_id) {
                return return_data("user_id and referrer_id are the same", 400);
            }
            $referral = Referral::where('user_id', $data->referrer_id)->where('referrer_id', $user_id)->first();
            if ($referral != null) {
                return return_data("Vice-versa referral is invalid", 400);
            }
            $referral = Referral::where('user_id', $user_id)->first();
            if ($referral != null) {
                return return_data($referral, 422);
            }
            $referral = new Referral;
            $referral->user_id = $user_id;
            $referral->referrer_id = $data->referrer_id;
            $referral->version = 3; // Membership Start
            $referral->save();

            $user = static::get_user($user_id);
            if ($user != null) {
                $user->referrer_id = $data->referrer_id;
                $user->save();
            }
            return return_data($referral, 201);
        } else {
            return return_data("Undefined referrer_id", 400);
        }
    }

    public function activate_user($user_id){

        $user = User::find($user_id);
        if($user){
            $user->status = 1;
            $user->agreed = 1;
			$user->request_confirmation_at = null;
            if($user->save()){
                if($user->status == 1){
                    Mail::to($user->email)->send(new ActivationEmail($user));
                }
                return true;
            }
        }
        return false;
    }

    public function disable_user($user_id){
        $user = User::find($user_id);
        if($user){
            $user->status = 0;
            if($user->save()){
                return true;
            }
        }
        return false;
    }

    public function ban_user($user_id,$reason='',$type='1'){
        $user = User::find($user_id);
        if($user <> null){
            $ban_reasons = json_decode($user->ban_reasons);
            if ($ban_reasons == null){
                $ban_reasons = [];
            }
            $data = [
                'reason' => $reason,
                'datetime' => ucwords(Carbon::now()->toDateTimeString())
            ];
            
            array_push($ban_reasons, $data);
            $user->ban_reasons = json_encode($ban_reasons);
            $user->status = 0;
            $user->ban = $type;
            $user->ban_at = date('Y-m-d H:i:s');
            if($user->save()){
                return true;
            }
        }
        return false;
    }

    public function unban_user($user_id){
        $user = User::find($user_id);
        if($user <> null){
            $user->ban = 0;
            if($user->save()){
                return true;
            }   
        }
        return false;
    }

    public function count_blog_post($user_id){
       $blog_post_count = Blog::where('user_id',$user_id)->count();
       return $blog_post_count;
    }

    public function sum_points($user_id){
       $blog_points = Blog::select(DB::raw('SUM(bu.points) as total_points'))
                              ->join('blog_user as bu','bu.blog_id','=','blog_post.blog_id')
                              ->where('bu.user_id',$user_id)->first();
       return $blog_points['total_points'];
    }

    public static function userAccessLimitations($user_id=0){
        $limitation = [];
        $voting_calc_allow = 0;
        $free_task = 0;
        $feat_kblog_blogger = 0;
        $task_featured_creator = 0;
        $gift_membership = 0;

        if($user_id == 0){
            $user_id = Auth::id();
        }
        $checkUser = User::find($user_id);

        $voting_calc = is_limitation_passed('bot-voting-weight-calculator',$user_id);
        if ($voting_calc['passed']) {
            if($voting_calc['data'] <> null){
                $voting_calc_allow = $voting_calc['data']->value;
            }
        }

        $free_tasks = is_limitation_passed('free-task',$user_id);
        if ($free_tasks['passed']) {
            if($free_tasks['data'] <> null){
                $free_task = $free_tasks['data']->value;
            }
        }

        $role_id = $checkUser->role()->id;
        $user_free_tasks = UserFreeTask::where('user_id',$user_id)->where('role_id',$role_id)->get();
        $covered_free_task = 0;
        if(count($user_free_tasks) > 0){
            foreach ($user_free_tasks as $free) {
                $covered_free_task += $free->completer_cnt;
            }

            if($covered_free_task > $free_task){
                $free_task = 0;
            }else{
                $free_task = $free_task - $covered_free_task;
            }
        }


        $kblog_blogger = is_limitation_passed('kblog-featured-blogger',$user_id);
        if ($kblog_blogger['passed']) {
            if($kblog_blogger['data'] <> null){
                $feat_kblog_blogger = $kblog_blogger['data']->value;
            }
        }

        $featured_creator = is_limitation_passed('task-featured-creator',$user_id);
        if ($featured_creator['passed']) {
            if($featured_creator['data'] <> null){
                $task_featured_creator = $featured_creator['data']->value;
            }
        }

        $gift_member = is_limitation_passed('gift-membership',$user_id);
        if ($gift_member['passed']) {
            if($gift_member['data'] <> null){
                $gift_membership = $gift_member['data']->value;
            }
        }

        $limitation['voting_calc_allow'] = $voting_calc_allow;
        $limitation['free_task'] = $free_task;
        $limitation['featured_kblog_blogger'] = $feat_kblog_blogger;
        $limitation['task_featured_creator'] = $task_featured_creator;
        $limitation['gift_membership'] = $gift_membership;

        return $limitation;

    }

    public static function userMembership($id=0){
        
        $user_id = Auth::id();
        if($id != 0){
            $user_id = $id;
        }

        $user = User::find($user_id);
        $membership = 'bronze';
        if($user->role()){
            $membership = $user->role()->slug;
        }
    
        return $membership;

    }

}