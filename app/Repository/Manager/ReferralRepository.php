<?php
 
 namespace App\Repository\Manager;
 
 use App\Contracts\Manager\ReferralInterface;
 use Illuminate\Support\Facades\Auth;
 use App\Traits\UtilityTrait;
 use App\Traits\Manager\UserTrait;
 use App\Helpers\UtilHelper;
 use App\Model\Settings;
 use App\Model\AdminActivity;
 use App\Model\Referral;
 use App\Model\ReferralChangeRequest;
 use App\Model\ReferralChangeTransactions;
 use App\User;
 use Carbon\Carbon;

 class ReferralRepository implements ReferralInterface
 {

    use UtilityTrait, UserTrait;

    public function index()
    {
        $point_system['key'] = settings('referral_point_system')->key;
 		$point_system['value'] = settings('referral_point_system')->value;
 		$point_system['description'] = settings('referral_point_system')->description;
 
 		$point['key'] = settings('referral_point')->key;
 		$point['value'] = settings('referral_point')->value;
 		$point['description'] = settings('referral_point')->description;
 
 		$point_system_selection = ['predefined', 'percentage'];
 
 		$cap['key'] = settings('referral_point_cap')->key;
 		$cap['value'] = settings('referral_point_cap')->value;
 		$cap['description'] = settings('referral_point_cap')->description;
 
 		$signup_reward['key'] = settings('signup_referral_reward')->key;
 		$signup_reward['value'] = settings('signup_referral_reward')->value;
 		$signup_reward['description'] = settings('signup_referral_reward')->description;
 
 		$social_connection['key'] = settings('social_connection_reward')->key;
 		$social_connection['value'] = settings('social_connection_reward')->value;
 		$social_connection['description'] = settings('social_connection_reward')->description;
 
        $point_system_selection = ['predefined', 'percentage'];
         
        return $this->response(null, static::responseJwtEncoder(compact('point_system', 'point', 'point_system_selection', 'cap', 'signup_reward', 'social_connection')), 200, 'success');     
    }

 	public function setReferralSettings($request)
 	{
        $key = $request->key;
        $value = $request->value;

 		if ($key == 'referral_point_system'){
 			$point_system_selection = ['predefined', 'percentage'];
 			if (!in_array($value, $point_system_selection)){
 				 record_activity(Auth::id(), 'referral manager', "Setting Settings [{$key}]=>Selection is value is not valid", 'Settings', 0, 'error');
                 record_admin_activity(Auth::id(), 2, "Setting Settings [{$key}]=>Selection is value is not valid", 'referral', 0);
                 return static::response('Selection is value is not valid!',null, 400, 'failed');
 			}
 		}
 		$settings = Settings::where('key', $key)->first();
        $msg = "[value]:{$settings->value}->{$value}";
        $category = "";
        $task_point_system = ['referral_point_system','referral_point'];
        if(in_array($key,$task_point_system)){
            $referral_point_system = Settings::where('key', 'referral_point_system')->first();
            $referral_point = Settings::where('key', 'referral_point')->first();
            if($key == 'referral_point_system'){
                $msg = "[referral_point_system]:{$referral_point_system->value}->{$value}, [referral_point]:{$referral_point->value}->{$referral_point->value}";
            }elseif($key == 'referral_point'){
                $msg = "[referral_point_system]:{$referral_point_system->value}->{$referral_point_system->value}, [referral_point]:{$referral_point->value}->{$value}";
            }
            $category = 'Task point system';

        }else if ($key == 'signup_referral_reward'){
            $signup_referral_reward = Settings::where('key', 'signup_referral_reward')->first();
            $msg = "[value]:{$signup_referral_reward->value}->{$value}";
            $category = 'Signup reward';
        }else if ($key == 'social_connection_reward'){
            $social_connection_reward = Settings::where('key', 'social_connection_reward')->first();
            $msg = "[value]:{$social_connection_reward->value}->{$value}";
            $category = 'Social Connection Reward';
        }

 		$settings->value = $value;
 		if($settings->save()){
            record_activity(Auth::id(), 'referral manager', "Setting Settings [{$key}]=>" . $msg, 'Settings', $settings->id, 'success');
            record_admin_activity(Auth::id(), 2, "Setting Settings [{$key}]=>" . $msg, 'referral', 1, $category);
            return static::response(title_case(str_replace('_', ' ', $key)) . " setting was successfully updated!",null, 200, 'success');
        }
 
        return static::response('Failed to update referral settings!',null, 400, 'failed');
     }
     
     public function taskPointSettingsHistory($request){
        $offset = $request->has('offset') ? (($request->offset == "") ? 0 : $request->offset) : 0;
        $limit  = $request->has('limit') ? (($request->limit == "") ? 10 : $request->limit) : 10;
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $data = [];
        $list = [];

        $query = AdminActivity::where('category','Task point system')
                                 ->where('status',1)
                                 ->where('action', 'LIKE', '%'."settings".'%');

        $count = $query->count();

        if($filter_date <> ''){
            $query->whereDate('created_at','=',$filter_date);
        }

        $settings = $query->offset($offset)->limit($limit)->orderByDesc('created_at')->get();

        if(count($settings) > 0){
            foreach($settings as $key1 => $value){
                $actions = explode(',',$value->action);
                $sets = [];
                foreach($actions as $key2 => $value2){
                    $zz = explode('->',$value2);
                    $sets[] = $zz[1];
                }

                $item = [
                    'point_system' => $sets[0],
                    'points' => $sets[1],
                    'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $value->created_at)->toDateTimeString(),
                    'admin_name' => $this->get_user($value->admin_id)->name
                ];
                $data[] = $item;
            }

            $date = array();
            foreach($data as $key => $row){
                $date[$key] = $row['updated_at'];
            }

            array_multisort($date,SORT_DESC,$data);


            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }    

        return static::response('No Data Fetched!',null, 400, 'failed');
     }

     public function signupRewardSettingsHistory($request){
        $offset = $request->has('offset') ? (($request->offset == "") ? 0 : $request->offset) : 0;
        $limit  = $request->has('limit') ? (($request->limit == "") ? 10 : $request->limit) : 10;
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        $data = [];
        $list = [];

        $query = AdminActivity::where('category','Signup reward')
                                 ->where('status',1)
                                 ->where('action', 'LIKE', '%'."settings".'%');
        $count = $query->count();
        if($filter_date <> ''){
            $query->whereDate('created_at','=',$filter_date);
        }
        $settings = $query->offset($offset)->limit($limit)->orderByDesc('created_at')->get();

        if(count($settings) > 0){
            foreach($settings as $key1 => $value){
                $action = explode('->',$value->action);

                $item = [
                    'reward' => $action[1],
                    'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $value->created_at)->toDateTimeString(),
                    'admin_name' => $this->get_user($value->admin_id)->name
                ];
                $data[] = $item;
            }

            $date = array();
            foreach($data as $key => $row){
                $date[$key] = $row['updated_at'];
            }

            array_multisort($date,SORT_DESC,$data);

            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }    

        return static::response('No Data Fetched!',null, 400, 'failed');
     }

     public function socialConnectSettingsHistory($request){
        $offset = $request->has('offset') ? (($request->offset == "") ? 0 : $request->offset) : 0;
        $limit  = $request->has('limit') ? (($request->limit == "") ? 10 : $request->limit) : 10;
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $data = [];
        $list = [];

        $query = AdminActivity::where('category','Social Connection Reward')
                                 ->where('status',1)
                                 ->where('action', 'LIKE', '%'."settings".'%');
        $count = $query->count();
        if($filter_date <> ''){
            $query->whereDate('created_at','=',$filter_date);
        }
        $settings = $query->offset($offset)->limit($limit)->orderByDesc('created_at')->get();

        if(count($settings) > 0){
            foreach($settings as $key1 => $value){
                $action = explode('->',$value->action);

                $item = [
                    'reward' => $action[1],
                    'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $value->created_at)->toDateTimeString(),
                    'admin_name' => $this->get_user($value->admin_id)->name
                ];
                $data[] = $item;
            }

            $date = array();
            foreach($data as $key => $row){
                $date[$key] = $row['updated_at'];
            }

            array_multisort($date,SORT_DESC,$data);

            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }    

        return static::response('No Data Fetched!',null, 400, 'failed');
     }

     public function referralChangeRequest($request){
        $requestor_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $new_referrer_id = $request->new_referrer_id;
        $reason = $request->reason;

        if($new_referrer_id){
            $check_user = User::find($new_referrer_id);
            if($check_user == null){
                return static::response('No data found for new referrer ID! Please try again..',null, 400, 'failed');
            }
        }
        if($requestor_id <> ''){
            $user = User::find($requestor_id);
            if($user){
                $referrer_id = $user->referrer_id;
                if($referrer_id <> null){
                    $req = ReferralChangeRequest::where('requestor_id',$requestor_id)->first();
                    if($req == null){
                        $req_model = new ReferralChangeRequest();
                        $req_model->requestor_id = $requestor_id;
                        $req_model->old_referrer_id = $referrer_id;
                        $req_model->new_referrer_id = $new_referrer_id;
                        $req_model->reason = $reason;
                        if($req_model->save()){
                            return static::response('Successfully sent request for referral change!',null, 200, 'success');
                        }
                    }else{
                        return static::response('Referral change is only allowed once!',null, 400, 'failed');
                    }
                }
            }
        }
        return static::response('Failed to send referral change request!',null, 400, 'failed');
     }

     public function approveReferralChange($request){
        $referral_req_id = $request->referral_req_id;
        $admin_id = $request->has('admin_id') ? $request->admin_id : Auth::id();

        if($admin_id){
            $admin = User::find($admin_id);
            if( $admin && $admin->type <> '9' ){
                return static::response('You don\'t have permission to approve referral change request!',null, 400, 'failed');
            }
        }

        if($referral_req_id <> ''){
            $checker = ReferralChangeRequest::where('id',$referral_req_id)->where('status',0)->first();
            if($checker <> null){
                $checker->status = ReferralChangeRequest::APPROVE;
                if($checker->save()){
                    $user = User::find($checker->requestor_id);
                    if($user){
                        $user->referrer_id = $checker->new_referrer_id;
                        if($user->save()){
                            $referral = Referral::where('user_id',$checker->requestor_id)->where('referrer_id',$checker->old_referrer_id)->first();
                            if($referral){
                                $referral->old_referrer_id = $checker->old_referrer_id;
                                $referral->referrer_id = $checker->new_referrer_id;
                                $referral->reason = $checker->reason;
                                if($referral->save()){
                                    $change_trans = new ReferralChangeTransactions();
                                    $change_trans->referral_tbl_id = $referral->id;
                                    $change_trans->referral_req_id = $referral_req_id;
                                    $change_trans->admin_id = $admin_id;
                                    if($change_trans->save()){
                                        return static::response('Successfully approved referral change request!',null, 200, 'success');
                                    }   
                                }
                            }
                        }
                    }
                }
            }else{
                return static::response('Referral change request cannot be found!',null, 400, 'failed');
            }
        }
        return static::response('Failed to approve referral change request!',null, 400, 'failed');
     }

     public function declineReferralChange($request){
        $referral_req_id = $request->referral_req_id;
        $reason = $request->reason;
        $admin_id = $request->has('admin_id') ? $request->admin_id : Auth::id();

        if($admin_id){
            $admin = User::find($admin_id);
            if( $admin && $admin->type <> '9' ){
                return static::response('You don\'t have permission to approve referral change request!',null, 400, 'failed');
            }
        }

        if($referral_req_id <> ''){
            $checker = ReferralChangeRequest::where('id',$referral_req_id)->where('status',0)->first();
            if($checker <> null){
                $checker->status = ReferralChangeRequest::DECLINE;
                if($checker->save()){
                    $change_trans = new ReferralChangeTransactions();
                    $change_trans->referral_req_id = $referral_req_id;
                    $change_trans->admin_id = $admin_id;
                    $change_trans->decline_reason = $reason;
                    if($change_trans->save()){
                        return static::response('Successfully declined referral change request!',null, 200, 'success');
                    }
                }
            }else{
                return static::response('Referral change request cannot be found!',null, 400, 'failed');
            }
        }
        return static::response('Failed to decline referral change request!',null, 400, 'failed');
    }

    public function referralChangeRequestList($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $query = ReferralChangeRequest::select(['referral_change_request.id',
                                                'referral_change_request.created_at',
                                                'referral_change_request.reason',
                                                'uo.name as old_referrer_name',
                                                'un.name as new_referrer_name',
                                                'ur.name as requestor_name'])
                                      ->leftJoin('users as uo','uo.id','=','referral_change_request.old_referrer_id')
                                      ->leftJoin('users as un','un.id','=','referral_change_request.new_referrer_id')
                                      ->leftJoin('users as ur','ur.id','=','referral_change_request.requestor_id')
                                      ->where('referral_change_request.status',0);
        $count = $query->count();
        if($filter_date <> ''){
            $query->whereDate('referral_change_request.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query->where('uo.name','LIKE','%'.$search_key.'%')
                  ->orWhere('un.name','LIKE','%'.$search_key.'%')
                  ->orWhere('ur.name','LIKE','%'.$search_key.'%');
        }
        $referral = $query->orderByDesc('referral_change_request.created_at')->offset($offset)->limit($limit)->get();
        $data = [];
        $list = [];

        if(count($referral) > 0){
            foreach($referral as $key => $value){
                $item = [
                    'request_id' => $value->id,
                    'name' => $value->requestor_name,
                    'requested_date' => Carbon::parse($value->created_at)->toDateString(),
                    'old_referrer' => $value->old_referrer_name,
                    'new_referrer' => $value->new_referrer_name,
                    'reason' => $value->reason
                ];
                $data[] = $item;
            }

            $list['list'] = $data;
            $list['total_count'] = $count;
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }

        return static::response('No Data Fetched!',null, 400, 'failed');
    }
    

    public function referralChangeHistory($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $query = ReferralChangeTransactions::select(['r.created_at as requested_dt',
                                                     'r.reason',
                                                     'referral_change_transactions.decline_reason',
                                                     'referral_change_transactions.referral_req_id',
                                                     'referral_change_transactions.created_at as trans_date',
                                                     'uo.name as old_referrer_name',
                                                     'un.name as new_referrer_name',
                                                     'um.name as admin_name',
                                                     'ur.name as requestor_name'])
                                            ->leftJoin('referral_change_request as r','r.id','=','referral_change_transactions.referral_req_id')
                                            ->leftJoin('users as um','um.id','=','referral_change_transactions.admin_id')
                                            ->leftJoin('users as uo','uo.id','=','r.old_referrer_id')
                                            ->leftJoin('users as un','un.id','=','r.new_referrer_id')
                                            ->leftJoin('users as ur','ur.id','=','r.requestor_id');
        $count = $query->count();

        if($filter_date <> ''){
            $query->whereDate('referral_change_transactions.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query->where('uo.name','LIKE','%'.$search_key.'%')
                  ->orWhere('un.name','LIKE','%'.$search_key.'%')
                  ->orWhere('ur.name','LIKE','%'.$search_key.'%')
                  ->orWhere('um.name','LIKE','%'.$search_key.'%');
        }
        $referral = $query->orderByDesc('referral_change_transactions.created_at')->offset($offset)->limit($limit)->get();
        $data = [];
        $list = [];

        if(count($referral) > 0){
            foreach($referral as $key => $value){
                $item = [
                    'request_id' => $value->referral_req_id,
                    'name' => $value->requestor_name,
                    'requested_date' => Carbon::parse($value->requested_dt)->toDateString(),
                    'old_referrer' => $value->old_referrer_name,
                    'new_referrer' => $value->new_referrer_name,
                    'updated_date' => $value->trans_date,
                    'status' => (new ReferralChangeRequest)->getStatusById($value->referral_req_id),
                    'admin_name' => $value->admin_name,
                    'request_reason' => $value->reason,
                    'decline_reason' => $value->decline_reason
                ];
                $data[] = $item;
            }
            $list['list'] = $data;
            $list['count'] = $count;
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched!',null, 400, 'failed');
    }
}
