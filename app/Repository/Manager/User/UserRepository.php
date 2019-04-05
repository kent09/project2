<?php

namespace App\Repository\Manager\User;

use App\User;
use App\Model\Balance;
use App\Model\AdminActivity;
use App\Model\SocialConnect;
use App\Traits\BankTrait;
use App\Traits\UtilityTrait;
use App\Traits\TaskTrait;
use App\Helpers\UtilHelper;
use Illuminate\Http\Request;
use App\Traits\Manager\UserTrait;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Manager\User\UserInterface;
use App\Repository\WalletRepository;
use Carbon\Carbon;

class UserRepository implements UserInterface
{
    use UtilityTrait, UserTrait, BankTrait, TaskTrait;

    private $page;
    private $paginate;

    public function __construct(Request $req)
    {
        $this->page = $req->has('page') ? intval($req->page) : 1;
        $this->paginate = $req->has('paginate') ? intval($req->paginate) : 10;
    }

    public function get_all_users($req)
    {
        $data = $this->all();
        $total = $data->total;
        $page = $this->page;
        $users = paginate($data->users, $this->paginate, $page);
        $items = [];
        $list = [];
        if($total > 0){
            foreach($users as $key => $value){
                $item = [
                    'name' => $value->name,
                    'email' => $value->email, 
                    'ip' => $value->ip,
                    'status' => $value->status(),
                    'registered_date' => ($value->created_at <> null) ? Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString() : $value->created_at,
                    'referred_by' => ($value->referrer_id <> 0) ? static::get_user($value->referrer_id)->name : $value->referrer_id,
                ];
                $items[] = $item;
            }

            $list['list'] = $items;
            $list['total'] = $total;

            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
        }
    }

    public function get_filtered_users($req)
    {
        if (!$req->has('status')) {
            return $this->response('Status not specified', null, 412, 'failed');
        } else {
            $status = $req->status;

            if ($status == 'active') {
                $data = $this->active();
            } elseif ($status == 'unverified') {
                $data = $this->unverified();
            } elseif ($status == 'unconfirmed') {
                $data = $this->unconfirmed();
            } elseif ($status == 'soft-banned') {
                $data = $this->soft_banned();
            } elseif ($status == 'hard-banned') {
                $data = $this->hard_banned();
            } elseif ($status == 'disabled') {
                $data = $this->disabled();
            } elseif ($status == 'requested-manual-confirmation') {
                $data = $this->requested_manual_confirmation();
            } elseif ($status == 'newly_registered'){
                $data = $this->newly_registered();
            } elseif ($status == 'banned'){
                return $this->getBannedUsers($req);
            }

            $total = $data->total;
            $users = $data->users;

            $list = [];
            $data_list = [];
            $users = paginate($data->users, $this->paginate, $this->page);

            if($total > 0){
                foreach($users as $key => $value){
                    $item = [
                        'username' => $value->username,
                        'name' => $value->name,
                        'email' => $value->email, 
                        'ip' => $value->ip,
                        'status' => $value->status(),
                        'email_verified' => ($value->verified == 1) ? 'yes' : 'no',
                        'registered_date' => ($value->created_at <> null) ? Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString() : $value->created_at,
                        'referred_by' => ($value->referrer_id <> 0) ? static::get_user($value->referrer_id)->name : $value->referrer_id,
                        'social_account' => $this->get_linked_social_connections($value->id)['data']
                    ];
                    $data_list[] = $item;
                }

                $list['list'] = $data_list;
                $list['total_count'] = $total;

                return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
            }

            return $this->response('No Data Fetched!', null, 201, 'success');
        }
    }

    public function search($req)
    {
        return $this->response('Wait lang sa. Hehe', null, 200, 'success');
    }

    public function user_counts()
    {
        $active = $this->active()->total;
        $unverified = $this->unverified()->total;
        $unconfirmed = $this->unconfirmed()->total;
        $soft_banned = $this->soft_banned()->total;
        $hard_banned = $this->hard_banned()->total;
        $disabled = $this->disabled()->total;
        $requested_manual_confirmation = $this->requested_manual_confirmation()->total;

        return $this->response(null, compact('active', 'unverified', 'unconfirmed', 'soft_banned', 'hard_banned', 'disabled', 'requested_manual_confirmation'), 200, 'success');
    }

    public function getStatistics(){
    

        $data = [
            'newly_registered' => $this->newly_registered()->total,
            'banned_users' => $this->banned_today()->total,
            'user_withdrawal' => $this->count_sup_for_approval_withdrawals(),
            'new_tasks' => $this->countNewTasks(),
            'daily_visitors' => $this->daily_visitors()->total,
            'unverfiied' => $this->unverified()->total,
            'unconfirmed' =>  $this->unconfirmed()->total,
            'soft_banned' => $this->soft_banned()->total,
            'hard_banned' => $this->hard_banned()->total,
            'requested_manual' => $this->requested_manual_confirmation()->total,
            'registered' => $this->active()->total,
            'disabled' => $this->disabled()->total,
            'all_users' => $this->all()->total
        ];

        return $this->response(null,static::responseJwtEncoder($data), 200, 'success');
    }
    
    
    public function deviceCount(){

        $device_count = $this->device_user_count();
        return $this->response(null,static::responseJwtEncoder($device_count), 200, 'success');
    }

    public function getBannedUsers($req){

       $limit = $req->has('limit') ? $req->limit : 10;
       $users = User::whereIn('ban', '>=' ,1)->get();
       $list = [];
       $data = [];
        if(count($users) > 0){
            foreach ($users as $row => $val){
                $reasons = json_decode($val->ban_reasons);
                foreach((array) $reasons as $reason){
                   $item = [
                        'username' => $val->username,
                        'name' => $val->name,
                        'type' => $val->status(),
                        'ip' => $val->ip,
                        'email_verified' => ($val->verified == 1) ? 'yes' : 'no',
                        'referred_by' => ($val->referrer_id <> 0) ? static::get_user($val->referrer_id)->name : $val->referrer_id,  
                        'banned_reason' => $reason->reason,
                        'ban_date' => ($reason->datetime <> null) ?  Carbon::createFromFormat('Y-m-d H:i:s',$reason->datetime)->toDateTimeString() : $reason->datetime                  
                    ];
                    $data[] = $item;
                }
            }

            $sort_date = array();
            foreach($data as $key => $row){
                $sort_date[$key] = $row['ban_date'];
            }

            array_multisort($sort_date,SORT_DESC,$data);
            $data = array_slice($data,0,$limit);

            $list['list'] = $data;
            $list['total_count'] = count($users);  

            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
       }
       return $this->response('No Data Fetched!',null, 201, 'success');
    }

    public function banUser($req){
        $user_id = $req->user_id;
        $reason = $req->reason;
        $ban_type = $req->ban_type; // soft or hard 

        
        if($user_id == ""){
            return $this->response('User ID is required!',null, 400, 'failed');
        }

        if($user_id == Auth::id()){
            record_activity(Auth::id(), 'user manager', "Not allowed to ban own account", 'User', $user_id, 'error');
            record_admin_activity(Auth::id(), 2, "Banning User [{$user_id}]=>Not allowed to ban own account", 'user', 0, 'Banning', $user_id);
            return $this->response('You can\'t ban yourself!',null, 400, 'failed');
        }

        if($ban_type == 'hard'){
            $type = 2;
        }else{
            $type = 1;
        }

        #checking if user is available or not ban
        $user = User::find($user_id);
        if($user == null){
            record_activity(Auth::id(), 'user manager', "User Not found {$user_id}", 'User', $user_id, 'error');
			record_admin_activity(Auth::id(), 2, "Banning User [{$user_id}]=>User not found [{$user_id}]", 'user', 0, 'Banning', $user_id);
            return $this->response('User not found!',null, 400, 'failed');
        }

        $msg = "[status]:{$user->status}->0, [ban]:{$user->ban}->2, [ban_at]:{$user->ban_at}->". date('Y-m-d H:i:s') . ", [REASON]:{$reason}";
        $ban = $this->ban_user($user_id,$reason,$type);
		if($ban){
            record_activity(Auth::id(), 'user manager', "User successfully banned", 'User', $user_id);
		    record_admin_activity(Auth::id(), 2, "Banning User [{$user_id}]=>" . $msg, 'user', 1, 'Banning', $user_id);
            return $this->response('User successfully banned!',null, 200, 'success');
        }
    }

    public function unbanUser($req){
        $user_id = $req->user_id;
        $now = date('Y-m-d H:i:s');

        if($user_id == ""){
            return $this->response('User ID is required!',null, 400, 'failed');
        }

        if($user_id == Auth::id()){
            record_activity(Auth::id(), 'user manager', "Not allowed to unban own account", 'User', $user_id, 'error');
			record_admin_activity(Auth::id(), 2, "Unbanning User [{$user_id}]=>Not allowed to unban own account", 'user', 0, 'Unbanning', $user_id);
            return $this->response('You can\'t un-ban yourself!',null, 400, 'failed');
        }
        
        #checking if user is available or not ban
        $user = User::find($user_id);
        if($user == null){
            record_activity(Auth::id(), 'user manager', "User Not found {$user_id}", 'User', $user_id, 'error');
			record_admin_activity(Auth::id(), 2, "Unbanning User [{$user_id}]=>User Not found [{$user_id}]", 'user', 0, 'Unbanning', $user_id);
            return $this->response('User not found!',null, 400, 'failed');
        }

        $msg = "[ban]:{$user->ban}->0, [unban_at]->{$now}";
        $unban = $this->unban_user($user_id);
		if($unban){
            record_activity(Auth::id(), 'user manager', "User successfully un-banned", 'User', $user_id);
		    record_admin_activity(Auth::id(), 2, "Unbanning User [{$user_id}]=>" . $msg, 'user', 1, 'Unbanning', $user_id);
            return $this->response('User was successfully un-banned!',null, 200, 'success');
        }   
    }

    public function accountSummary($username = ''){
        if($username == ''){
            return $this->response('Username is required!',null, 400, 'failed');
        }

        $user = User::where('username',$username)->first();
        if($user <> null){
            $balance = (new WalletRepository())->getHoldings($user->id, true);

            $data = [
                'name' => $user->name,
                'username' => $user->username,
                'referral_code' => $user->ref_code,
                'email' => $user->email,
                'registered_date' => ($user->created_at <> null) ?  Carbon::createFromFormat('Y-m-d H:i:s',$user->created_at)->toDateTimeString() : $user->created_at,
                'referred_by' => ($user->referrer_id <> 0) ? static::get_user($user->referrer_id)->name : $user->referrer_id,
                'account_type' => ($user->type == 9) ? 'admin' : 'regular',
                'verified' => ($user->verified == 1) ? 'yes' : 'no',
                'status' => $user->status(),
                'total_sup' => $balance['total'],
                'hold_sup' => $balance['hold'],
                'available_sup' => $balance['available'],
                'pending_receive' => $balance['pending'],
                'premined_sup' => $balance['premine'],
                'bonus_sup' => $balance['bonus'],
                'latest_used_ip' => $this->get_latest_used_ip($user->id,true)
            ];

            return $this->response(null,static::responseJwtEncoder($data), 200, 'success');
        }

        return $this->response('No Data Fetched!',null, 400, 'failed');
    }

    public function bannedReasons($user_id){

        $admin = AdminActivity::where('affected_user_id',$user_id)->where('status',1)->where('category','=','Banning')->get();
        
        $ban_reasons = [];
        $list = [];
        if(count($admin) > 0){
            foreach($admin as $key => $value){
                $data = explode(',',$value->action);
                $bans_arr = [];
                foreach($data as $ban => $ban_val){
                    if($ban <= 2){
                        $bans = explode('->',$ban_val);
                        $bans_arr[] = $bans[1];
                    }

                    if($ban == 3){
                        $bans = explode(':',$ban_val);
                        $bans_arr[] = $bans[1];
                    }
                }
                $bans_arr[4] = $value->admin_id;
                $ban_reasons[] = $bans_arr;
            }

            foreach($ban_reasons as $key => $reason){
                $item = [
                    'reason' => $reason[3],
                    'type' => $this->get_ban_status($reason[1]),
                    'banned_date' => $reason[2],
                    'admin_name' => $this->get_user($reason[4])->name
                ];

                $list[] = $item;
            }

            $sort_date = array();
            foreach($list as $key => $row){
                $sort_date[$key] = $row['banned_date'];
            }

            array_multisort($sort_date,SORT_DESC,$list);

            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
        }
        return $this->response('No Data Fetched!',null, 400, 'failed');
    }

    function disableUser($req){
        $user_id = $req->user_id;

        if($user_id == Auth::id()){
            record_activity(Auth::id(), 'user manager', "Not allowed to change own status", 'User', $user_id, 'error');
			record_admin_activity(Auth::id(), 2, "Status Change [{$user_id}]=>Not allowed to change own status", 'user', 0, 'Change Status', $user_id);
            return $this->response('You can\'t change your status!',null, 400, 'error');
        }

        $user = User::find($user_id);
        if($user){
            $msg = "[status]:{$user->status}->0";
            $disable = $this->disable_user($user_id);
            if($disable){
                record_activity(Auth::id(), 'user manager', "Change status to 0", 'User', $user->id);
		        record_admin_activity(Auth::id(), 2, "Status Change [{$user_id}]=>" . $msg, 'user', 1, 'Change Status', $user->id);
                return $this->response('Successfully disabled user account!',null, 200, 'success');
            }
            return $this->response('Failed to disable user account!',null, 400, 'failed');
        }

        return $this->response('User not found!',null, 400, 'failed');
    }

    function activateUser($req){
        $user_id = $req->user_id;

        if($user_id == Auth::id()){
            record_activity(Auth::id(), 'user manager', "Not allowed to change own status", 'User', $user_id, 'error');
			record_admin_activity(Auth::id(), 2, "Status Change [{$user_id}]=>Not allowed to change own status", 'user', 0, 'Change Status', $user_id);
            return $this->response('You can\'t change your status!',null, 400, 'error');
        }

        $user = User::find($user_id);
        if($user){
            $msg = "[agreed]:{$user->agreed}->1, [request_confirmation_at]:{$user->request_confirmation_at}->null, [status]:{$user->status}->1";
            $activate = $this->activate_user($user_id);
            if($user->status == 1){
                $msg .= ' and successfully sent an activation email.';
            }
            if($activate){
                record_activity(Auth::id(), 'user manager', "Change status to 1", 'User', $user->id);
		        record_admin_activity(Auth::id(), 2, "Status Change [{$user_id}]=>" . $msg, 'user', 1, 'Change Status', $user->id);
                return $this->response('Successfully activated user account!',null, 200, 'success');
            }
            return $this->response('Failed to activate user account!',null, 400, 'failed');
        }
        return $this->response('User not found!',null, 400, 'failed');
    }

    function setStatusMulti($req){
        $ids = $request->ids;
        $status = $request->status;

        if(count($ids) > 0){
            foreach($ids as $id){
                if ($id == Auth::id()){
                    record_activity(Auth::id(), 'user manager', "Not allowed to change own status", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, 'Change Status [{$id}]=>Not allowed to change own status', 'user', 0, 'Change Status', $id);
                    return $this->response('You can\'t change your status!',null, 400, 'error');
                }

                $user = User::where('id', $id)->where('ban', 0)->first();
                if($user == null){
                    record_activity(Auth::id(), 'user manager', "Some User not found to ban", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, "Change Status [{$id}]=>Some User not found to ban [{$id}]", 'user', 0, 'Change Status', $id);
                    return $this->response('Some user not found or banned!',null, 400, 'error');
                }

                if($status == 1){
                    $msg = "[agreed]:{$user->agreed}->1, [request_confirmation_at]:{$user->request_confirmation_at}->null, [status]:{$user->status}->1";
                    $set_status = $this->activate_user($id);
                }else{
                    $msg = "[status]:{$user->status}->0";
                    $set_status = $this->disable_user($id);
                }

                record_activity(Auth::id(), 'user manager', "Change status to {$status}", 'User', $id);
                record_admin_activity(Auth::id(), 2, "Changing Status [{$id}]=>" . $msg, 'user', 1, 'Change Status', $id);
            }
            $status_desc = ($status == 1) ? 'activated' : 'disabled';
            return $this->response('Successfully '.$status_desc.' user account/s!',null, 200, 'success');
        }

        return $this->response('No selected user!',null, 400, 'failed');
    }

    function banUserMulti($req){
        $ids = $request->ids;
        $reason = $request->status;

        if(count($ids) > 0){
            foreach($ids as $id){
                if ($id == Auth::id()){
                    record_activity(Auth::id(), 'user manager', "Not allowed to ban own account", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, "Banning Multi User [{$id}]=>Not allowed to ban own account", 'user', 0, 'Banning', $id);
                    return $this->response('You can\'t change your status!',null, 400, 'error');
                }

                $user = User::find($id);
                if($user == null){
                    record_activity(Auth::id(), 'user manager', "Some User Not found {$id}", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, "Banning Multi User [{$id}]=>Some User Not found [{$id}]", 'user', 0, 'Banning', $id);
                    return $this->response('Some user not found!',null, 400, 'error');
                }
                $msg = "[status]:{$user->status}->0, [ban]:{$user->ban}->2, [ban_at]:{$user->ban_at}->" . date('Y-m-d H:i:s') . ", [reason]:{$reason}";
                $this->ban_user($id,$reason);

                record_activity(Auth::id(), 'user manager', "User successfully banned", 'User', $id);
				record_admin_activity(Auth::id(), 2, "Banning Multi User [{$id}]=>" . $msg, 'user', 1, 'Banning', $id);
            }

            return $this->response('Successfully banned user account/s!',null, 200, 'success');
        }   

        return $this->response('No selected user!',null, 400, 'failed');
    }

    function unbanUserMulti($req){
        $ids = $request->ids;
        $now = date('Y-m-d H:i:s');

        if(count($ids) > 0){
            foreach($ids as $id){
                if ($id == Auth::id()){
                    record_activity(Auth::id(), 'user manager', "Not allowed to unban own account", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, "Unbanning Multi User [{$id}]=>Not allowed to unban own account", 'user', 0, 'Unbanning', $id);
                    return $this->response('You can\'t change your status!',null, 400, 'error');
                }

                $user = User::find($id);
                if($user == null){
                    record_activity(Auth::id(), 'user manager', "Some User Not found {$id}", 'User', $id, 'error');
					record_admin_activity(Auth::id(), 2, "Unbanning Multi User [{$id}]=>Some User Not found [{$id}]", 'user', 0, 'Unbanning', $id);
                    return $this->response('Some user not found!',null, 400, 'error');
                }

                $msg = "[ban]:{$user->ban}->0. [unban_at]->{$now}";
                $this->unban_user($id);
                record_activity(Auth::id(), 'user manager', "User successfully unbanned", 'User', $id);
				record_admin_activity(Auth::id(), 2, "Unbanning Multi User [{$id}]=>" . $msg, 'user', 1, 'Unbanning', $id);
            }
            return $this->response('Successfully unbanned user account/s!',null, 200, 'success');
        }
        return $this->response('No selected user!',null, 400, 'failed');
    }

    function countSocialConStatus($req){
        $social = $req->has('social') ? $req->social : "";
        if($social == 'all'){
            $social = "";
        }

        $soft_unlinked = $this->soft_unlinked($social)->total;
        $hard_unlink_request = $this->hard_unlink_request($social)->total;
        $new_connected = $this->new_social_connected($social)->total;

        return $this->response(null,compact('soft_unlinked','hard_unlink_request','new_connected'), 200, 'success');
        
    }

    function socialConnectAll($req){
        $list = [];
        $data = [];
        $social = $req->has('social') ? $req->social : '';
        $filter_date = "";
        if($req->has('filter_date')){
            if($req->filter_date <> ''){
                $filter_date = Carbon::parse($req->filter_date)->toDateString();
            }
        }

        $social_query = SocialConnect::query();
        if($filter_date <> ''){
                $social_query = $social_query->whereDate('created_at',$filter_date);
        }

        if($social <> ''){
                $social_query = $social_query->where('social',$social);
        }

        $social_con = $social_query->orderByDesc('created_at')->get();
        $social_total_count = count($social_con);
        $social_con = paginate($social_con, $this->paginate, $this->page);


        if(count($social_con) > 0){
            foreach($social_con as $con => $value){
                $item = [
                    'username' => $this->get_user($value->user_id)->username,
                    'name' => $this->get_user($value->user_id)->name,
                    'connected_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'account_name' => $value->account_name,
                    'social' => $value->social,
                    'account_id' => $value->account_id,
                    'status' => $value->socialConnectStatus()
                ];
                $data[] = $item;
            }
            $list['list'] = $data;
            $list['total_count'] = $social_total_count;
            
            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return $this->response('No Data Fetched!',null, 400, 'failed');        
    }

    function hardUnlinkRequestList($req){
        $list = [];
        $data = [];
        $social = $req->has('social') ? $req->social : '';
        $filter_date = "";
        if($req->has('filter_date')){
            if($req->filter_date <> ''){
                $filter_date = Carbon::parse($req->filter_date)->toDateString();
            }
        }

        $social_query = SocialConnect::query();
        if($filter_date <> ''){
                $social_query = $social_query->whereDate('created_at',$filter_date);
        }

        if($social <> ''){
                $social_query = $social_query->where('social',$social);
        }

        $social_con = $social_query->where('hard_unlink_status',SocialConnect::hu_status_requested)->orderByDesc('created_at')->get();
        $social_total_count = SocialConnect::where('hard_unlink_status',SocialConnect::hu_status_requested)->count();
        $social_con = paginate($social_con, $this->paginate, $this->page);

        if(count($social_con) > 0){
            foreach($social_con as $con => $value){
                $item = [
                    'username' => $this->get_user($value->user_id)->username,
                    'name' => $this->get_user($value->user_id)->name,
                    'requested_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$value->updated_at)->toDateTimeString(),
                    'account_name' => $value->account_name,
                    'reason' => $value->reason,
                    'social' => $value->social
                ];
                $data[] = $item;
            }
            $list['list'] = $data;
            $list['total_count'] = $social_total_count;
            
            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return $this->response('No Data Fetched!',null, 400, 'failed');       
    }

    function hardUnlinkedList($req){
        $list = [];
        $data = [];
        $social = $req->has('social') ? $req->social : '';
        $filter_date = "";
        if($req->has('filter_date')){
            if($req->filter_date <> ''){
                $filter_date = Carbon::parse($req->filter_date)->toDateString();
            }
        }

        $admin = AdminActivity::where('field','social')->where('status',1)->where('category','hard-unlink')->get();
        $admin_count = count($admin);
        $admin = paginate($admin, $this->paginate, $this->page);
        $hard_unlink = [];
        if(count($admin) > 0){
            foreach($admin as $key => $value){
                $action_data = explode(',',$value->action);
                $hard_unlink_arr = [];
                foreach($action_data as $unlink => $unlink_val){
                    $link = explode('->',$unlink_val);
                    $hard_unlink_arr[] = $link[1];
                }
                $hard_unlink_arr[5] = $value->admin_id;
                $hard_unlink_arr[6] = $value->affected_user_id;
                $hard_unlink[] = $hard_unlink_arr;
            }

            foreach($hard_unlink as $key => $val){
                $account_name = "";
                if($val[4]){
                    $social_info = SocialConnect::where('id',$val[4])->first();
                    $account_name = $social_info->account_name;
                }
               
                $item = [
                    'username' => $this->get_user($val[6])->username,
                    'name' => $this->get_user($val[6])->name,
                    'hard_unlinked_date' => $val[3],
                    'account_name' => $account_name,
                    'reason' => $val[1],
                    'requested_date' => $val[2],
                    'admin_name' => $this->get_user($val[5])->name
                ];

                if($filter_date <> ''){
                    $hard_unlinked_date = Carbon::parse($val[3])->toDateString();
                    if($hard_unlinked_date == $filter_date){
                        $data[] = $item;
                    }
                }else{
                    $data[] = $item;
                } 
            }

            $sort_date = array();
            foreach($data as $key => $row){
                $sort_date[$key] = $row['hard_unlinked_date'];
            }

            array_multisort($sort_date,SORT_DESC,$data);

            $list['list'] = $data;
            $list['total_count'] = $admin_count;

            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');    
        }
        return $this->response('No Data Fetched!',null, 400, 'failed');       
    }

    function softUnlinkedList($req){
        $list = [];
        $data = [];
        $social = $req->has('social') ? $req->social : '';
        $filter_date = "";
        if($req->has('filter_date')){
            if($req->filter_date <> ''){
                $filter_date = Carbon::parse($req->filter_date)->toDateString();
            }
        }

        $social_query = SocialConnect::query();
        if($filter_date <> ''){
                $social_query = $social_query->whereDate('created_at',$filter_date);
        }

        if($social <> ''){
                $social_query = $social_query->where('social',$social);
        }

        $social_con = $social_query->where('status',2)->orderByDesc('created_at')->get();
        $social_total_count = SocialConnect::where('status',2)->count();
        $social_con = paginate($social_con, $this->paginate, $this->page);

        if(count($social_con) > 0){
            foreach($social_con as $con => $value){
                $soft_link_date = $value->soft_link_at; 
                if($value->soft_link_at == ''){
                    $soft_link_date = $value->updated_at;
                }
                $item = [
                    'username' => $this->get_user($value->user_id)->username,
                    'name' => $this->get_user($value->user_id)->name,
                    'soft_unlinked_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$soft_link_date)->toDateTimeString(),
                    'account_name' => $value->account_name,
                    'social' => $value->social
                ];
                $data[] = $item;
            }

            $sort_date = array();
            foreach($data as $key => $row){
                $sort_date[$key] = $row['soft_unlinked_date'];
            }
            array_multisort($sort_date,SORT_DESC,$data);
            
            $list['list'] = $data;
            $list['total_count'] = $social_total_count;
            
            return $this->response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return $this->response('No Data Fetched!',null, 400, 'failed');       
    }
}