<?php
 
 namespace App\Repository\Manager;
 
 use App\Contracts\Manager\BankInterface;
 use App\Helpers\UtilHelper;
 use App\Model\Settings;
 use App\User;
 use App\Model\Task;
 use App\Model\TaskUser;
 use App\Model\DbWithdrawal;
 use App\Model\BtcWithdrawal;
 use Carbon\Carbon;
 use App\Repository\WalletRepository;
 use App\Traits\UtilityTrait;
 use App\Traits\TaskTrait;
 use Illuminate\Support\Facades\DB;

 class BankRepository implements BankInterface
 {
    use UtilityTrait, TaskTrait;
    
    public function index()
    {
        $payout_onhold['key'] = settings('bank_on_hold_duration')->key;
 		$payout_onhold['value'] = settings('bank_on_hold_duration')->value;
 		$payout_onhold['description'] = settings('bank_on_hold_duration')->description;

         
        return $this->response(null, static::responseJwtEncoder(compact('payout_onhold')), 200, 'success');     
    }

    public function supForApproval($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        $query = DbWithdrawal::select(['dbwithdrawal.*','u.name'])
                             ->leftJoin('users as u','u.id','=','dbwithdrawal.user_id')
                             ->where('dbwithdrawal.status', DbWithdrawal::FOR_APPROVAL_STATUS);

        $count = $query->count();

        if($filter_date <> ''){
            $query->whereDate('dbwithdrawal.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query = $query->where('u.name','LIKE','%'.$search_key.'%');
        }
        
        $for_approval = $query->orderBy('dbwithdrawal.created_at', 'desc')->offset($offset)->limit($limit)->get();

        $data = [];
        $list = [];
        if(count($for_approval) > 0){
            foreach($for_approval as $key => $value){
                $holdings = (new WalletRepository())->getHoldings($value->user_id, true);
                $balance = $holdings['total'];
                $item = [
                    'user_id' => $value->user_id,
                    'datetime' => Carbon::parse($value->created_at)->toDateTimeString(),
                    'block' => $value->block,
                    'amount' => $value->balance,
                    'sender_name' => $value->name,
                    'status' => $value->status(),
                    'balance' => $balance,
                ];

                $data[] = $item;
            }
            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched!',null, 400, 'failed');
    }

    public function btcForApproval($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $query = BtcWithdrawal::select(['bitcoinwithdrawl.*','u.name'])
                              ->leftJoin('users as u','u.id','=','bitcoinwithdrawl.user_id')
                              ->where('bitcoinwithdrawl.status', BtcWithdrawal::FOR_APPROVAL_STATUS);

        $count = $query->count();

        if($filter_date <> ''){
            $query->whereDate('bitcoinwithdrawl.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query = $query->where('u.name','LIKE','%'.$search_key.'%');
        }

        $for_approval = $query->orderBy('bitcoinwithdrawl.created_at', 'desc')->offset($offset)->limit($limit)->get();
        
        $data = [];
        $list = [];
        if(count($for_approval) > 0){
            foreach($for_approval as $key => $value){
                $holdings = (new WalletRepository())->getBTCHoldings($value->user_id);
                $balance = $holdings['total'];
                $item = [
                    'datetime' => Carbon::parse($value->created_at)->toDateTimeString(),
                    'address' => $value->address,
                    'amount' => $value->btc,
                    'sender_name' => $value->name,
                    'status' => $value->status(),
                    'balance' => $balance
                ];

                $data[] = $item;
            }
            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched!',null, 400, 'failed');
    }

    public function setSupWithdrawalStatus($data){
        $type = $data['status'] == 1 ? 'Approved' : 'Declined';
		$withdrawal = DbWithdrawal::where('id', $data['id'])->first();
		if ($withdrawal != null) {
			$withdrawal->status = $data['status'];
			if ($withdrawal->save()) {
                return static::response('Withdrawal is ' . $type ,null, 200, 'success');
			}
        }
        return static::response('Failed to set withdrawal status to ' .$type ,null, 400, 'failed');
    }


    public function setBtcWithdrawalStatus($data){
        $type = $data['status'] == 1 ? 'Approved' : 'Declined';
		$withdrawal = BtcWithdrawal::where('id', $data['id'])->first();
		if ($withdrawal != null) {
			$withdrawal->status = $data['status'];
			if ($withdrawal->save()) {
                return static::response('Withdrawal is ' . $type ,null, 200, 'success');
			}
        }
        return static::response('Failed to set withdrawal status to ' .$type ,null, 400, 'failed');
    }

    public function taskRevokeList($request){
        $task_id = $request->has('task_id') ? $request->task_id : "";
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $query = TaskUser::select(['task_user.*', 'b.title', 'task_user.user_id AS completer_id',
                                'us.name AS creator_name', 'u.name AS completer', 'u.username',
                                'us.username AS creator_username', 'task_user.updated_at as revoked_dt',
                                DB::raw('IFNULL(bt.reason,"") AS revoke_reason')])
                            ->leftJoin('users as u','u.id','=','task_user.user_id')
                            ->leftJoin('users as us','us.id','=','task_user.task_creator')
                            ->leftJoin('tasks as b', 'b.id','=','task_user.task_id')
                            ->leftJoin('banned_user_task as bt', function($q){
                                $q->where('bt.user_id','=','task_user.user_id')
                                  ->where('bt.task_id','=','task_user.task_id');
                            })
                            ->where('task_user.revoke', 1)
                            ->where('b.status', 1);
                                        
        if($task_id <> "" && $task_id <> 0){
            $query->where('task_user.task_id',$task_id);
        }

        $count = $query->count();

        if($filter_date <> ''){
            $query = $query->whereDate('task_user.updated_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query = $query->where('us.name','LIKE','%'.$search_key.'%')
                           ->orWhere('b.title','LIKE','%'.$search_key.'%')
                           ->orWhere('u.name','LIKE','%'.$search_key.'%');
        }

        $task_revoked = $query->orderByDesc('task_user.updated_at')->offset($offset)->limit($limit)->get();

        $data = [];
        $list = [];

        if(count($task_revoked) > 0){
            foreach($task_revoked as $key => $value){
                $item = [
                    'revoked_date' => Carbon::parse($value->revoked_dt)->toDateTimeString(),
                    'creator_name' => $value->creator_name,
                    'creator_username' => $value->creator_username,
                    'title' => $value->title,
                    'reward' => $value->reward,
                    'completer' => $value->completer,
                    'status' => static::getTaskRewardWithdrawalStatus($value->task_id),
                    'revoke_type' => static::getRevokeRewardType($value->task_id,$value->task_creator,$value->completer_id),
                    'revoke_reason' => $value->revoke_reason
                ];

                $data[] = $item;
            }

            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }

        return static::response('No Data Fetched!',null, 400, 'failed');
    }   

    public function taskCreatorStats($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $query = Task::select(['u.name',
                               'u.username',
                                DB::raw('count(tu.id) AS count_revoked'),
                               'u.status AS user_status', 
                               'tasks.title',
                               'tasks.reward',
                               'tasks.id AS task_id',
                               'tasks.user_id AS task_user_id',
                               'tasks.created_at AS task_created'])
                    ->leftJoin('users AS u','u.id','=','tasks.user_id')
                    ->leftJoin('task_user AS tu','tu.task_id','=','tasks.id')
                    ->where('tasks.status',1)
                    ->where('tu.revoke',1);

        $count = $query->count();

        if($filter_date <> ''){
            $query = $query->whereDate('tasks.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $query = $query->where('tasks.title','LIKE','%'.$search_key.'%')
                           ->orWhere('u.name','LIKE','%'.$search_key.'%');
        }

        $task = $query->groupBy("tasks.id")
                      ->havingRaw('count(tu.id) > 0')
                      ->orderByDesc('tasks.created_at')
                      ->offset($offset)
                      ->limit($limit)->get();

        $data = [];
        $list = [];
        if(count($task) > 0){
            foreach($task as $key => $value){
                $completer = TaskUser::leftJoin('tasks AS t','t.id','=','task_user.task_id')
									->where('task_id',$value->task_id)
									->where('t.status',1)
                                    ->where('task_user.revoke',0)->count();
                                    
                $item = [
                    'task_created' => $value->task_created,
                    'creator_name' => $value->name,
                    'creator_username' => $value->username,
                    'task_title' => $value->title,
                    'task_id' => $value->task_id,
                    'reward' => $value->reward,
                    'total_completers' => $completer,
                    'total_revoked' => $value->count_revoked
                ];
                $data[] = $item;
            }

            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }

        return static::response('No Data Fetched!',null, 400, 'failed');
    }

    public function reinstateReward($request){
        $task_id = $request->task_id;
		$user_id = $request->user_id;

		$task_user = TaskUser::where('task_id',$task_id)->where('user_id',$user_id)->first();

        if( $task_user ){
			$task_creator_id = $task_user->task_creator;

			$available = (new WalletRepository())->getholdings($task_creator_id, true);

			if ($available['available'] >= $task_user->reward) {
                $task_revoke = TaskUser::find($task_user->id);
                $task_revoke->revoke = 0;
                if($task_revoke->save()){
                    (new WalletRepository())->getholdings($task_creator_id, true);
                    (new WalletRepository())->getholdings($task_user->user_id, true);
                    return static::response('Successfully reinstated reward!',null, 200, 'success');
                }
            } else {
                return static::response('Not enough Sup!',null, 400, 'failed');
            }
        }
        return static::response('Failed to reinstate reward!',null, 400, 'failed');
    }

}
