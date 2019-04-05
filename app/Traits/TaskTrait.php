<?php

namespace App\Traits;


use App\Model\ActivityScoreTask;
use App\Model\BannedUserTask;
use App\Model\BlockUserTask;
use App\Model\CategoryTask;
use App\Model\ConnectionTask;
use App\Model\DbWithdrawal;
use App\Model\FollowerTask;
use App\Model\ReputationTask;
use App\Model\SocialConnect;
use App\Model\SubCategoryTask;
use App\Model\TaskCategory;
use App\Model\TaskCompletionDetail;
use App\Model\TaskHidden;
use App\Model\TaskOptionDetail;
use App\Model\TaskTransactionHistory;
use App\Model\TaskUser;
use App\Model\TaskWizard;
use App\Model\UserFollower;
use App\Model\Settings;
use App\Model\KryptoniaTaskComment;
use App\Model\KryptoniaTaskSubComment;
use App\Model\UserReputationActivityScore;
use App\Model\TaskDeleted;
use App\Model\RawQueries;
use App\User;
use Carbon\Carbon;
use App\Model\Task;
use function foo\func;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

trait TaskTrait
{
    use UserTrait;

    public function listOfActiveTask($request) {

        $user = Auth::user();

        $tasks = (new RawQueries())->activeTask($request);
       
        return $tasks;
    }

    public function getRequirement($user_id, $task_id){
        $requirement = array();
        $user_activity_reputation = UserReputationActivityScore::where('user_id', $user_id)->first();
      
        $activity_query = ActivityScoreTask::query();
        $reputation_query = ReputationTask::query();
        $task_query = TaskUser::query();

        $requirement['is_activity_passed'] = false;
        $requirement['is_reputation_passed'] = false;
        $requirement['is_follower'] = false;

        $activity_score = $activity_query->where('task_id', $task_id)
            ->where('active',1);
        $reputation_score = $reputation_query->where('task_id', $task_id)
            ->where('active',1);
     
        $task_count = $task_query->where('task_creator', $user_id)->count();

        $task_revoked_count = $task_query->where('task_creator',$user_id)->where('revoke',1)->count();
        $avg = ($task_count > 0) ? ($task_revoked_count / $task_count) * 100 : 0;
        
       
        if($user_activity_reputation){
            $requirement['is_reputation_passed'] = ($reputation_score->count() == 0) ? true :
                $reputation_query->where('reputation','<=', $user_activity_reputation->reputation)->count() ? true : false;
            $requirement['is_activity_passed'] = ($activity_score->count() == 0) ? true :
                $activity_score->where('activity_score','>=', $user_activity_reputation->activity_score)->count() ? true : false;
            $requirement['is_follower'] = static::isFollowed($user_id, Auth::id());
        }
        else{
            $requirement['is_reputation_passed'] = false;
            $requirement['is_activity_passed'] = false;
            $requirement['is_follower'] = false;
        }

        $requirement['is_high_risk'] = ($avg >= 50);

        return $requirement;
    }

    private function getStatus($task_id){
        $status = "active";
        $task = Task::find($task_id);
        $status = ($task->expired_date <= Carbon::now()) ? 'expired' : $status;
        $status = ($task->final_cost == 0) ? 'completed' : $status;
        $status = ($task->status == 0) ? 'deactivated' : $status;

        return $status;
    }

    public function getTaskCompletionStatus($task_id,$user_id){
        $user_id = $user_id ?? Auth::id();
        $status = "";
        $settings = Settings::where('key', 'bank_on_hold_duration')->first();
        $bank_on_hold_duration = $settings->value;
        $task = TaskUser::where('user_id',$user_id)->where('task_id',$task_id)->first();
        if($task <> null){
            $completion_date = new Carbon($task->created_at);
            $now = Carbon::now();
            $diff = $completion_date->diffInDays($now);
            if($task->revoke == 0){
                $status = ($diff > $bank_on_hold_duration) ? 'completed' : 'pending';
            }else{
                $status = "revoked";
            }
        }

        return $status;
    }

    public function getTaskRewardWithdrawalStatus($task_id){
        return $this->getStatus($task_id);
    }

    public function getRevokeRewardType($task_id,$task_user_id,$user_id){
        $user_id = $user_id ?? Auth::id();
        $type ="";
        $is_revoked = 0;
        $is_blocked = 0;

        $revoked = BannedUserTask::where('user_id',$user_id)->where('task_id',$task_id)->first();
        if($revoked <> null){
            $is_revoked = 1;
        }

        $blocked = BlockUserTask::where('user_id',$user_id)->where('task_user_id',$task_user_id)->first();
        if($blocked <> null){
            $is_blocked = 1;
        }

        if($is_revoked == 1 && $is_blocked == 1){
            $type = "Revoke / Block";
        }elseif($is_revoked == 1 && $is_blocked == 0){
            $type = "Revoke";
        }

        return $type;
    }

    public function listOfAllOwnTask($request) {
        // $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        // $search_key = $request->has('search_key') ? $request->search_key : "";
        // $filter = $request->has('filter') ? $request->filter : "";

        // $task_del = TaskDeleted::where('user_id', $user_id)->where('status',1)->get(['task_id']);
        
        // $task_deleted = array_map(function($val){
        //     return $val['task_id'];
        // },$task_del->toArray());

        
        // $task_query = Task::query();
        // $task_query = $task_query
        //     ->select(['tasks.id as task_id', 'tasks.*'])
        //     ->where('tasks.user_id', $user_id)
        //     ->whereNotIn('tasks.id',$task_deleted);
        
        // if($filter <> ''){
        //     $task_query = $task_query->where('category', $request->filter);
        // }

        // if($search_key <> ''){
        //     $task_query = $task_query->where(function($q) use ($search_key){
        //         $q->where('title','LIKE','%'.$search_key.'%')->orWhere('slug','LIKE','%'.$search_key.'%');
        //     });
        // }

        // $task_query_dummy = $task_query;
        // $task_count = $task_query_dummy->get()->toArray();
        // $task_query = $task_query->orderBy('tasks.updated_at', 'desc')->skip($request->offset)->take($request->limit);

        // $tasks = $task_query->get(['id','user_id','task_id']);

        // // $tasks = array_map(function($task){
        // //     $task['requirement_status'] = $this->getRequirement($task['user_id'], $task['task_id']);
        // //     $task['status_str'] = $this->getStatus($task['task_id']);
        // //     $task['available_completer'] = ($task['total_point'] - $task['total_rewards']);
        // //     return $task;
        // // },$tasks->toArray());
    
        
        // $tasks_count = Task::where('tasks.user_id', $user_id)->count();

        // return ['task' => $tasks, 'count' => count($task_count)];

        $tasks = (new RawQueries())->ownTask($request);

        return ['task' => $tasks, 'count' => count($tasks)];
    }

    public function listOfHiddenTask($request) {
//        $user_id = $request->user_id ?? Auth::id();
        // $user_id = Auth::id();
        // $search_key = $request->has('search_key') ? $request->search_key : "";
        // $filter = $request->has('filter') ? $request->filter : "";
        // $task_query = TaskHidden::query();

        // $task_query = $task_query->with(['task' => function($qry){
        //         $qry->select(['id','user_id','title','description','reward','final_cost','status','total_point','total_rewards','image','created_at','category','expired_date','slug']);
        //     },
        //     'task.user' => function($qry){
        //         $qry->select(['id','email','name','username']);
        //     }])
        //     ->leftJoin('tasks as t','t.id','task_hiddens.task_id')
        //     ->leftJoin('users as u', 'u.id', '=', 't.user_id')
        //     ->where('task_hiddens.user_id', $user_id)
        //     ->where('task_hiddens.hidden', (bool) 1);
    
        
        // if($filter <> ''){
        //     $task_query = $task_query->where('t.category', $request->filter);
        // }

        // if($search_key <> ''){
        //     $task_query = $task_query->where(function($q) use ($search_key){
        //                                 $q->where('t.title','LIKE','%'.$search_key.'%')
        //                                   ->orWhere('u.name','LIKE','%'.$search_key.'%')
        //                                   ->orWhere('t.slug','LIKE','%'.$search_key.'%');
        //                             });
        // }

        // $task_query_dummy = $task_query;
        // $task_count = $task_query_dummy->get()->toArray();
        // $task_query = $task_query->orderByDesc('task_hiddens.id')->offset($request->offset)->limit($request->limit);
        // $tasks = $task_query->get();

        // $tasks = array_map(function($task) {
        //     $task['task'][0]['requirement_status'] = $this->getRequirement($task['task'][0]['user_id'], $task['task_id']);
        //     $task['task'][0]['status_str'] = $this->getStatus($task['task_id']);
        //     $task['task'][0]['available_completer'] = ($task['task'][0]['total_point'] - $task['task'][0]['total_rewards']);
        //     return $task;
        // },$tasks->toArray());

        $tasks = (new RawQueries())->hiddenTask($request);

        return ['task' => $tasks];
    }

    public function listOfCompletedTask($request) {
        // $user_id = Auth::id();
        // $search_key = $request->has('search_key') ? $request->search_key : "";
        // $filter = $request->has('filter') ? $request->filter : "";

        // $task_query = TaskUser::query();

        // $task_query = $task_query->with(['creator' => function($qry){
        //     $qry->select(['id','email','name','username']);
        // }, 'taskInfo' => function($qry){
        //     $qry->select(['id','user_id','title','description','reward','total_point','total_rewards','image','created_at','category','expired_date','slug', 'final_cost', 'status']);
        // }])
        //     ->join('tasks as t','t.id','task_user.task_id')
        //     ->leftJoin('users as u', 'u.id', '=', 't.user_id')
        //     ->where('task_user.user_id', $user_id);

        // if($filter <> ''){
        //     $task_query = $task_query->where('t.category', $request->filter);
        // }
        // if($search_key <> ''){
        //     $task_query = $task_query->where(function($q) use ($search_key){
        //                                 $q->where('t.title','LIKE','%'.$search_key.'%')
        //                                   ->orWhere('u.name','LIKE','%'.$search_key.'%')
        //                                   ->orWhere('t.slug','LIKE','%'.$search_key.'%');
        //                             });
        // }

        // $task_query_dummy = $task_query;
        // $task_count = $task_query_dummy->get()->toArray();
        // $task_query = $task_query->orderBy('task_user.created_at', 'desc')->skip($request->offset)->take($request->limit);
        // $tasks = $task_query->get();

        // $tasks = array_map(function($task) {
        //     $task['task_info']['requirement_status'] = $this->getRequirement($task['creator']['id'], $task['task_id']);
        //     $task['task_info']['status_str'] = $this->getStatus($task['task_id']);
        //     $task['task_info']['available_completer'] = ($task['task_info']['total_point'] - $task['task_info']['total_rewards']);
        //     return $task;
        // },$tasks->toArray());


        $tasks = (new RawQueries())->completedTask($request);

        return ['task' => $tasks, 'count' => count($tasks)];
    }

    public function unHideTaskStatus($task_id, $user_id) {
        $task = Task::where('id',$task_id)->first();
        $bol = false;
        $msg = 'Failed to unhide task!';

            $task = TaskHidden::where('user_id', $user_id)->where('task_id', $task_id)->where('hidden', (bool) 1)->first();
            if($task) {
                $task->hidden = (bool) 0;
                if($task->save()){
                    $bol = true;
                    $msg = "Successfully unhide task!";
                }
            }

        return ['bol' => $bol, 'error_msg' => $msg];
    }


    /**
     * @param $id
     * @param $user_id
     *
     * @return bool|int
     */
    public function deleteTaskStatus($id, $user_id) {
        $task = Task::find($id);
        $user = User::find($user_id);
        if($user->type !== 9) {
            if(static::taskIfFromWizard($id))
                return 2;
        }

        if($task) {
            $task->status = 0;
            if($task->save()){
                $delete = new TaskDeleted();
                $delete->task_id = $id;
                $delete->user_id = $user_id;
                if($delete->save()){
                    return true;
                }
            }
        }
        return false;
    }

    public function activateTaskStatus($task_id) {
        $task = Task::where('id', $task_id)->where(function($query) use ($task_id) {
            $query->where('status', (bool) 0)
                ->orWhere('expired_date', '<', app('carbon')->now());
        })->first();
        return $task;
    }

    public function deActivateTaskStatus($task_id) {
        $task = Task::where('id', $task_id)->where('expired_date', '>=', Carbon::now())->where('status', (bool) 1)->first();
        return $task;
    }

    public function editTaskStatus($task_id) {
        $task = Task::with([
            'activityScoreTask' => function($q) {
                $q->where('active', (bool) 1);
            },
            'reputationTask' => function($q) {
                $q->where('active', (bool) 1);
            },
            'followerOption' => function($q) {
                $q->where('active', (bool) 1);
            },
            'attachmentOption' => function($q) {
                $q->where('status', (bool) 1);
            },
            'connectionOption' => function($q) {
                $q->where('status', 1);
            }
        ])->where('slug', $task_id)
            #->where('expired_date', '>=', app('carbon')->now())
            ->where('status', (bool) 1)
            ->first();

        $user_id = Auth::id();
        $user = User::where(function($query) use ($user_id) {
            $query->where('id', $user_id);
        })->first(['id', 'name', 'email', 'type']);
        return compact('task', 'user');
    }

    public function toBeUpdatedTask($id) {
        return Task::where(function ($query) use ($id) {
            $query->where('status', (bool) 1)
                ->where('id', $id);
        })->first();
    }

    #utility
    public static function failedTaskWizard(int $task_id) {
        $task_wizard = Task::where('id', $task_id)->where('status', 0)->first();
        if( $task_wizard ) {
            $task_wizard->status = 0;
            if($task_wizard->save()) {
                return app('response')->json([
                    'status' => 'error',
                    'data' => null,
                    'code' => 401,
                    'message' => 'Error in creating the task. Please try again!'
                ]);
            }
        }
    }

    public function checkPendingWithdrawal($id) {
        $db_withdrawal = DbWithdrawal::where('user_id', $id)
            ->where('status', 0)
            ->orWhere(function($query) {
                $query->where('ban', 1)
                    ->whereNotNull('ban_at');
            })->first();
        return $db_withdrawal;
    }


    public static function taskIfFromWizard($id) {
        $wizard_task = TaskWizard::where('task_id', $id)->first();
        if($wizard_task)
            return true;
        return false;
    }

    // get metatags monmon
    function getUrlData($url) {
        try {
            $data =  file_get_contents($url);
            $title = preg_match('/<title[^>]*>(.*?)<\/title>/ims', $data, $matches) ? $matches[1] : null;
            $tags = get_meta_tags($url, true);
            $tags = [
                'title' => $title,
                'tags' => $tags
            ];
            return $tags;
        } catch (\Throwable $th) {
            return [
                'title' => null,
                'tags' => null
            ];
        }
       
    }

    // end monnmon

    public function showTaskAssociate(string $task_slug) {
        $user_id = Auth::id();
        $task = static::activeTaskFindByIdOrSlug($task_slug, 'slug');
        
        $fee_charge = static::taskFeeCharge();
        $user_limitations = static::userAccessLimitations();
        $free_tasks = $user_limitations['free_task'];
        $requirement_limitation = static::taskRequirementLimitation();
    
        $metatags = [];
       
        if( $task ) {
            $attachment_img = null;
            if($user_id != $task->user_id){
                $attachment_img = TaskCompletionDetail::where('user_id',$user_id)->where('task_id',$task->id)->first();
                if($attachment_img){
                    $attachment_img = $attachment_img->attachment_file;
                }
            }   

            $metatags = $this->getUrlData($task->task_link);
            // dd($metatags);
            $completed = false;
            #check if task is completed
            if( static::taskIfCompleted($task->id, $user_id) )
                $completed = true;

            $block_list = false;
            #check if user in block list
            if( static::checkIfUserInBlockList($user_id, $task->user_id) )
                $block_list = true;

            $banned = false;
            #check if user banned in this task
            if( static::checkIfUserInRevokeList($user_id, $task->id) )
                $banned = true;

            $hidden = false;
            #check if task is currently hidden
            if( static::taskCheckIfHidden($user_id, $task->id) )
                $hidden = true;

            $fb_connect = false;
            

            $steemit_connect = static::checkUserSocialConnect($task->user_id, 'steemit');
            $google_connect = static::checkUserSocialConnect($task->user_id, 'google-plus');
            $instagram_connect = static::checkUserSocialConnect($task->user_id, 'instagram');
            $fb_connect = static::checkUserSocialConnect($task->user_id, 'facebook');
            $twitter_connect = static::checkUserSocialConnect($task->user_id, 'twitter');

            // $instagram_connect = false;
            // #check user social connects [instagram]
            // if( static::checkUserSocialConnect($user_id, 'instagram') )
            //     $instagram_connect = true;
            
            // $google_connect = false;
            
            // #check user social connects [google]
            // if( static::checkUserSocialConnect($user_id, 'google-plus') )
            //     $google_connect = true;
            

            // $twitter_connect = false;
            // #check user social connects [twitter]
            // if( static::checkUserSocialConnect($user_id, 'twitter') )
            //     $twitter_connect = true;

            // $steemit_connect = false;
            // #check user social connects [google]
            // if( static::checkUserSocialConnect($user_id, 'steemit') )
            //     $steemit_connect = true;

            // #check user social connects [facebbok]
            // if( static::checkUserSocialConnect($user_id, 'facebook') )
            //     $fb_connect = true;

           
            $follower = false;
            #check if task creator is followed.
            if( static::isFollowed($task->user_id, $user_id) )
                $follower = true;

            $attachment = false;
            #check if task has attachment
            if( static::checkTaskAttachmentRequired($task->id) )
                $attachment = true;


            $completed_task = TaskUser::where('user_id',$user_id)
                ->where('task_creator', $task->user_id)
                ->get(['task_id', 'status']);
            
            $completed_task_id = array_map(function($val){
                return $val['task_id'];
            }, $completed_task->toArray());

            
            #other task limit by 3.
            $other_task = static::userOtherTask(3, $task, $completed_task_id);

            #activity and reputation temp
            $task_id = $task->id;
            $activity_score_required = ActivityScoreTask::where(function ($query) use ($task_id) {
                $query->where('task_id', $task_id)
                    ->where('active', (bool) 1);
            })->pluck('activity_score');

            $reputation_score_required = ReputationTask::where(function ($query) use ($task_id) {
                $query->where('task_id', $task_id)
                    ->where('active', (bool) 1);
            })->pluck('reputation');
        
            $reputation_required = ReputationTask::where(function ($query) use ($task_id) {
                $query->where('task_id', $task_id)
                    ->where('active', (bool) 1);
            })->first();

            $connection_required = ConnectionTask::where(function($query) use ($task_id) {
                $query->where('task_id', $task_id)
                    ->where('status', (bool) 1);
            })->first();

            $follower_required = FollowerTask::where(function($query) use ($task_id, $user_id) {
                $query->where('task_id', $task_id)
                    ->where('task_user_id', $user_id)
                    ->where('active', (bool) 1);
            })->first();

            if(strlen($task->title) <= 40){
                $task_title = $task->title;
            }else{
                $task_title = substr($task->title,0,40) . '...';
            }
            
            $own_task = false;
            if($task->user_id == $user_id){
                $own_task = true;
            }
            $data = [
                'attachment_img' => $attachment_img,
                'completed' => $completed,
                'block_list' => $block_list,
                'banned' => $banned,
                'hidden' => $hidden,
                'fb_connect' => $fb_connect,
                'twitter_connect' => $twitter_connect,
                'google_connect' => $google_connect,
                'instagram_connect' => $instagram_connect,
                'steemit_connect' => $steemit_connect,
                'task' => $task,
                'task_title' => $task_title,
                'other_task' => $other_task,
                'activity_score_required' => $activity_score_required,
                'reputation_score_required' => $reputation_score_required,
                'available_completer' => static::completerAvailable($task_id),
                'author_name' => static::taskAuthorCredential($task_id, 'name'),
                'author_username' => static::taskAuthorCredential($task_id, 'username'),
                'author_register_date' => static::taskAuthorCredential($task_id, 'register_date'),
                'can_complete' => static::userCanComplete($task, $user_id),
                'failed_requirements' => static::taskRequirementFailed($task, $user_id),
                'follower' => $follower,
                'attachment' => $attachment,
                'follower_option' => ($follower_required) ? true : false,
                'connection_option' => ($connection_required) ? true : false,
                'reputation_option' => ($reputation_required) ? true : false,
                'status_str' => $this->getStatus($task_id),
                'metatags' => $metatags,
                'own_task' => $own_task,
                'fee_charge' => $fee_charge,
                'requirement_limitation' => $requirement_limitation,
                'free_tasks' => $free_tasks
            ];
            return compact('data');
        } else {
            $task = Task::where('slug', $task_slug)->first();
            if(strlen($task->title) <= 40){
                $task_title = $task->title;
            }else{
                $task_title = substr($task->title,0,40) . '...';
            }
            $data = [
                'can_complete' => false,
                'task' => $task,
                'author_name' => static::taskAuthorCredential($task->id, 'name'),
                'author_register_date' => static::taskAuthorCredential($task->id, 'register_date'),
                'task_title' => $task_title,
                'available_completer' => static::completerAvailable($task->id),
                'fee_charge' => $fee_charge,
                'requirement_limitation' => $requirement_limitation,
                'free_tasks' => $free_tasks
            ];
            if( $task->final_cost <= 0 ) {
                $data['invalid'] = 'Task Depleted';
                return compact('data');
            }
            if( $task->expired_date < app('carbon')->now() ) {
                $data['invalid'] = 'Task Expired';
                return compact('data');
            }
            if( !$task->status ) {
                $data['invalid'] = 'Task Deleted or Archived';
                return compact('data');
            }
        }
        return false;
    }

    public function listOfTaskDetails($request){
        $user_id = Auth::id();

        $tasks = Task::where('slug', $request->slug)->where('status', 1)->first();
        $task_id = $tasks['id'];
        $task_user_id = $tasks['user_id'];
        $task_creator_name = User::where('id',$tasks['user_id'])->pluck('name');

        $created_date = Carbon::parse($tasks['created_at'])->format('jS \o\f F, Y g:i:s a');

        $activity_required = ActivityScoreTask::where(function ($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('active', (bool) 1);
        })->pluck('activity_score');

        $reputation_required = ReputationTask::where(function ($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('active', (bool) 1);
        })->pluck('reputation');

        $follower_required = FollowerTask::where(function($query) use ($task_id, $user_id) {
            $query->where('task_id', $task_id)
                ->where('task_user_id', $user_id)
                ->where('active', (bool) 1);
        })->first();

        $has_follow = UserFollower::where(function ($query) use ($user_id, $task_user_id) {
            $query->where('user_id', $task_user_id)
                ->where('follower_id', $user_id)
                ->where('status', (bool) 1);
        })->first();

        if($has_follow != null){
            $has_follow = true;
        }else{
            $has_follow = false;
        }

        $attachment_required =  self::checkTaskAttachmentRequired($task_id);

        $count = Task::with(['completer' => function($query) {
            $query->where('revoke', 0);
        }])->where('slug', $request->slug)->where('status', 1)->first();

        $banned_user_tasks = BannedUserTask::where('user_id', $user_id)->where('task_id', $tasks->id)->first();
        $total_completers = count($count->completer);

        $slugs = $tasks->slug;

        return ['task' => $tasks, 'slug' => $slugs, 'banned_user_task' => $banned_user_tasks, 'total_completer' => $total_completers , 'activity_score' => $activity_required ,'reputation' => $reputation_required, 'follower_required' => $follower_required , 'has_follow' => $has_follow , 'attachment_required' => $attachment_required ,'created_date' => $created_date, 'creator_name' => $task_creator_name];
    }


    protected function uploadTaskImage($task_image, $task_id, $format, $type = 'task', $filename) : bool {

        $filename = ($filename == "") ? $task_id .'.'. $format : $filename;

        $target_path = app()->basePath('public/image/uploads/tasks/');

        if($type === 'attachment')
            $target_path = app()->basePath('public/image/uploads/tasks-attachment/');

        if( false === File::exists($target_path) )
            File::makeDirectory($target_path, 0777, true);

        $data = '';
        if (preg_match('/^data:image\/(\w+);base64,/', $task_image)) {
            $data = substr($task_image, strpos($task_image, ',') + 1);
            $data = base64_decode($data);
        }

        if(file_put_contents($target_path.$filename, $data)) {
            if( $type === 'attachment' ) {
                $completion_detail_option = new TaskCompletionDetail();
                $completion_detail_option->task_id = $task_id;
                $completion_detail_option->user_id = Auth::id();
                $completion_detail_option->attachment_file = $filename;
                if( $completion_detail_option->save() )
                    return true;
            } elseif( $type === 'task' ) {
                $task = Task::find($task_id);
                $task->image = $task_id . '.' . $format;
                if( $task->save() )
                    return true;
            }
        } else return false;
    }

    protected function urlSlugGenerator(string $task_title, int $task_id) : bool {
        $slugify = str_slug($task_title);
        $slug = Task::where(function($query) use ($slugify) {
            $query->where('slug', $slugify);
        })->get();

        if( !$slug ) {
            $task = Task::find($task_id);
            $task->slug = $slugify;
            if( $task->save() )
                return true;
        } else {
            $task = Task::find($task_id);
            $task->slug = $slugify . base_convert( rand(10000, 99999), 20, 36 );
            if( $task->save() )
                return true;
        }
        return false;
    }

     public function countTaskActiveComments($task_id) {
        $count_comments = 0;
        $count_replies = 0;
        $comments = KryptoniaTaskComment::with('taskCommentDetail')->where('task_id', $task_id)->active()->get();
        if(count($comments) > 0){
            $replies = 0;
            foreach($comments as $comment){
                $count_replies = KryptoniaTaskSubComment::with('taskSubCommentDetail')->where('parent_comment_id', $comment->id)->active()->count();
                $replies += $count_replies;
            }
            $count_comments = count($comments) + $replies;
        }

        return $count_comments;
    }

    public function countTaskActiveReplies($comment_id) {
        $count_replies = KryptoniaTaskSubComment::with('taskSubCommentDetail')->where('parent_comment_id', $comment_id)->active()->count();
        return $count_replies;
    }


    public function countNewTasks()
    {
        $now = Carbon::now()->toDateString();
        $tasks = Task::whereDate('created_at',$now)->count();
        return $tasks;
    }

    public function countTaskCompleter($slug){
        $completer = 0;

        if($slug <> ''){
            $task = Task::with(['completer' => function($query) {
                $query->where('revoke', 0);
            }])->where('slug', $slug)->where('status', 1)->first();
    
            if($task <> null){
                $completer = count($task->completer);
            }
        }
    
        return $completer;
    }

    public function countTaskUserRevoke($task_id=''){
        $task_revoked_count = 0;
        if($task_id != ""){
            $task_revoked_count = TaskUser::where('task_id',$task_id)->where('revoke',1)->count();
        }
        return $task_revoked_count;
      

    }

    public function taskFeeCharge(){
        $task_fee_charge = is_limitation_passed('task-fee-charge');
        $fee_charge = 0;
        if ($task_fee_charge['passed']) {
            if($task_fee_charge['data'] <> null){
                $fee_charge = $task_fee_charge['data']->value/100;
            }
        }
        return $fee_charge;
    }

    public function taskRequirementLimitation(){
        $user = Auth::user();

        $limitation = [];
        $attachment_required = is_limitation_passed('attachment-required');
        $attachment_req = 0;
        if ($attachment_required['passed']) {
            if($attachment_required['data'] <> null){
                $attachment_req = $attachment_required['data']->value;
            }
        }

        $only_followers_option = is_limitation_passed('only-followers-option');
        $only_followers = 0;
        if ($only_followers_option['passed']) {
            if($only_followers_option['data'] <> null){
                $only_followers = $only_followers_option['data']->value;
            }
        }

        $reputation_option = is_limitation_passed('reputation-option');
        $reputation_req = 0;
        if ($reputation_option['passed']) {
            if($reputation_option['data'] <> null){
                $reputation_req = $reputation_option['data']->value;
            }
        }

        $only_connection_option = is_limitation_passed('only-connection-option');
        $only_connection = 0;
        if ($only_connection_option['passed']) {
            if($only_connection_option['data'] <> null){
                $only_connection = $only_connection_option['data']->value;
            }
        }

        if($user->type == '9'){
            $attachment_req = 1;
            $only_followers = 1;
            $reputation_req = 1;
            $only_connection = 1;
        }
        
        $limitation['attachment_required'] = $attachment_req;
        $limitation['only_followers'] = $only_followers;
        $limitation['reputation_option'] = $reputation_req;
        $limitation['only_connection'] = $only_connection;

        return $limitation;

    }

    /**
     * @param int $task_id
     *
     * @return bool|string
     */
    protected function generateTaskURLHelper(int $task_id) {
        $task = Task::where(function($query) use ($task_id) {
            $query->where('id', $task_id)
                ->where('status', (bool) 1)
                ->where('expired_date', '>=', app('carbon')->now());
        })->first();
        if( $task ) {
            if( !$task->slug ) {
                $task->slug = str_slug($task->title);
                if( $task->save() )
                    return $task->slug;
            } else {
                return $task->slug;
            }
        } else return false;
        return false;
    }

    protected static function completerAvailable(int $task_id) {
        $task = Task::select(['total_point', 'total_rewards'])->where('id', $task_id)->first();
        $remaining = 0;
        if( $task ) {
            if( $task['total_point'] >= $task['total_rewards'] ) {
                $remaining = $task['total_point'] - $task['total_rewards'];
            }
            return $remaining;
        }
    }

    

    #utility

    protected static function taskFindBySlug(string $slug) {
        return Task::select([
            'title', 'description', 'task_link', 'image', 'category', 'reward', 'total_point'
        ])->where('slug', $slug)->first();
    }

    protected static function activeTaskFindByIdOrSlug($identifier , string $type) {

        switch ($type) {
            case 'id':
                return Task::where(function($query) use ($identifier) {
                    $query->where('id', $identifier)
                        ->where('status', (bool) 1);
                })->sharedLock()->first();
            break;
            case 'slug':
                return Task::where(function($query) use ($identifier) {
                    $query->where('slug', $identifier)
                        ->where('status', (bool) 1);
                })->sharedLock()->first();
            break;
        }
    }

    protected static function checkTaskActivityRequired(int $task_id, int $user_id) : bool {
        $activity_required = ActivityScoreTask::where(function ($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('active', (bool) 1);
        })->first();

        $activity_score = UserReputationActivityScore::where('user_id', $user_id)->first();

        if( $activity_required ) {
            if( $activity_required['activity_score'] <= $activity_score['activity_score'])
                return true;
            return false;
        } else return true;
    }

    protected static function checkTaskReputationRequired(int $task_id, int $user_id) : bool {
        $reputation_required = ReputationTask::where(function ($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('active', (bool) 1);
        })->first();

        $reputation_score = UserReputationActivityScore::where('user_id', $user_id)->first();

        if( $reputation_required ) {
            if( $reputation_required['reputation'] <= $reputation_score['reputation'] )
                return true;
            return false;
        } else return true;
    }

    protected static function checkUserSocialConnect(int $user_id, string $social)  {
        $social = SocialConnect::where(function ($query) use ($user_id, $social) {
            $query->where('user_id', $user_id)
                ->where('social', $social)
                ->where('status', 1);
        })->first();
        
        switch ($social['social']) {
            case 'facebook':
                return [
                    'connected' => true,
                    'link' => 'https://facebook.com/' . $social['account_id']
                ];
            break;

            case 'steemit':
            return [
                'connected' => true,
                'link' => 'https://steemit.com/@'. $social['account_id']
            ];
            break;

            case 'twitter':
            return [
                'connected' => true,
                'link' => 'https://twitter.com/' . $social['account_name']
            ];
            break;

            case 'instagram':
            return [
                'connected' => true,
                'link' => 'https://instagram.com/' . $social['account_name']
            ];
            break;

            case 'googleplus':
            return [
                'connected' => true,
                'link' => 'https://plus.google.com/' . $social['account_id']
            ];
            break;

            default:
            return [
                'connected' => false
            ];
            break;
        }
    }

    protected static function userOtherTask(int $limit = 0, $task, $completed) {
        
        return Task::with(['user' => function($query) {
                    $query->select(['id', 'username', 'name', 'has_avatar']);
                }])->where(function($query) use ($task, $completed) {
                    $query->where('id', '<>', $task->id)
                        ->where('status', (bool) 1)
                        ->where('expired_date', '>=', app('carbon')->now())
                        ->where('final_cost', '<>', 0)
                        ->whereNotIn('id', $completed)
                        ->where('user_id', $task->user_id);
                })->limit($limit)->get();
    }

    protected static function checkTaskAttachmentRequired(int $task_id) : bool {
        $task_option_detail = TaskOptionDetail::where(function ($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('status', (bool) 1);
        })->first();

        if($task_option_detail <> null)
            return true;
        return false;
    }

    protected static function isFollowed(int $task_user_id, int $user_id) : bool {
        $follower = UserFollower::where(function ($query) use ($user_id, $task_user_id) {
                        $query->where('user_id', $task_user_id)
                            ->where('follower_id', $user_id)
                            ->where('status', (bool) 1);
                    })->first();

        if( $follower )
            return true;
        return false;
    }

    protected static function checkTaskFollowerRequired(int $task_id, int $task_user_id, int $user_id) : bool {
        $follower_required = FollowerTask::where(function($query) use ($task_id, $task_user_id) {
            $query->where('task_id', $task_id)
                ->where('task_user_id', $task_user_id)
                ->where('active', (bool) 1);
        })->first();

        $follower = UserFollower::where(function ($query) use ($user_id, $task_user_id) {
            $query->where('user_id', $task_user_id)
                ->where('follower_id', $user_id)
                ->where('status', (bool) 1);
        })->first();

        if( $follower_required ) {
            if( $follower )
                return true;
            return false;
        } else return true;
    }

    protected static function checkTaskConnectionRequired($task, $task_completer_id) : bool {
        $task_id = $task->id;
        $user_id = $task->user_id;
        $connection = ConnectionTask::where(function($query) use ($task_id) {
            $query->where('task_id', $task_id)
                ->where('status', (bool) 1);
        })->first();
        $followed = UserFollower::where(function($query) use ($user_id, $task_completer_id) {
            $query->where('user_id', $user_id)
                ->where('follower_id', $task_completer_id)
                ->where('status', (bool) 1);
        })->first();
        $follower = UserFollower::where(function($query) use ($user_id, $task_completer_id) {
            $query->where('user_id', $task_completer_id)
                ->where('follower_id', $user_id)
                ->where('status', (bool) 1);
        })->first();
        if( $connection ) {
            if( $follower AND $followed )
                return true;
            return false;
        } else return true;
    }

    protected static function taskCheckIfHidden(int $task_id, int $user_id) : bool {
        $task_hidden = TaskHidden::where(function($query) use ($task_id, $user_id) {
            $query->where('task_id', $task_id)
                ->where('user_id', $user_id)
                ->where('hidden', 1);
        })->first();
        if( $task_hidden )
            return true;
        return false;
    }

    protected static function checkIfUserInBlockList(int $user_id, int $task_user_id) : bool {
        $blocked = BlockUserTask::where(function($query) use ($user_id, $task_user_id) {
            $query->where('user_id', $user_id)
                ->where('task_user_id', $task_user_id)
                ->where('block', (bool) 1);
        })->first();

        if( $blocked )
            return true;
        return false;
    }

    protected static function checkIfUserInRevokeList(int $user_id, int $task_id) : bool {
        $revoked = BannedUserTask::where(function($query) use ($user_id, $task_id) {
            $query->where('user_id', $user_id)
                ->where('task_id', $task_id)
                ->where('revoked', (bool) 1);
        })->first();
        if( $revoked )
            return true;
        return false;
    }

    protected static function taskIfCompleted(int $task_id, int $user_id) : bool {
        $completed = TaskUser::where(function($query) use($task_id, $user_id) {
            $query->where('task_id', $task_id)
                ->where('user_id', $user_id);
        })->first();
        if( $completed )
            return true;
        return false;
    }

    protected static function activateTaskWizard(int $task_user_id) :bool {
        $task = Task::where(function($query) use ($task_user_id) {
            $query->where('user_id', $task_user_id)
                ->where('status', (bool) 0);
        })->first();
        if( $task ) {
            $task->status = (bool) 1;
            if( $task->save() )
                return true;
        }
        return false;
    }

    protected static function checkCompleterIsConnection(int $user_id, int $task_user_id) : bool {
        $followed = UserFollower::where(function($query) use ($user_id, $task_user_id) {
            $query->where('user_id', $task_user_id)
                ->where('follower_id', $user_id)
                ->where('status', (bool) 1);
        })->first();
        $follower = UserFollower::where(function($query) use ($user_id, $task_user_id) {
            $query->where('user_id', $user_id)
                ->where('follower_id', $task_user_id)
                ->where('status', (bool) 1);
        })->first();

        if( $followed AND $follower )
            return true;
        return false;
    }

    protected static function taskAuthorCredential(int $task_id, string $type) {
        switch($type) {
            case 'name':
                $name = Task::with(['user' => function($query) {
                    $query->select(['id', 'name']);
                }])->where('id', $task_id)->first();

                return $name->user['name'];
            break;
            case 'username':
                $name = Task::with(['user' => function($query) {
                    $query->select(['id', 'username']);
                }])->where('id', $task_id)->first();
                return $name->user['username'];
            break;
            case 'register_date':
                $name = Task::with(['user' => function($query) {
                    $query->select(['id', 'created_at']);
                }])->where('id', $task_id)->first();
                return Carbon::parse($name->user['created_at'])->toDateString();
            break;
        }
    }

    protected static function userCanComplete($task, $user_id) {

        $can_complete_container = [];
        $affirmative = 'true';
        $negative = 'false';

        #check if task is connection required
        if( static::checkTaskConnectionRequired($task, $user_id) ) {

            array_push($can_complete_container, $affirmative);
        } else {
            array_push($can_complete_container, $negative);
        }

        #check if task is follower required
        if( static::checkTaskFollowerRequired($task->id, $task->user_id, $user_id) ) {
            array_push($can_complete_container, $affirmative);
        } else {
            array_push($can_complete_container, $negative);
        }

        #check task reputation score required
        if( static::checkTaskReputationRequired($task->id, $user_id) ) {
            array_push($can_complete_container, $affirmative);
        } else {
            array_push($can_complete_container, $negative);
        }

        #check task activity score required
        if( static::checkTaskActivityRequired($task->id, $user_id) ) {
            array_push($can_complete_container, $affirmative);
        } else {
            array_push($can_complete_container, $negative);
        }


        if( in_array($negative, $can_complete_container) )
            return false;
        return true;

    }

    protected static function taskRequirementFailed($task, $user_id) {
        $failed_requirement = new \stdClass;

        if( !static::checkTaskConnectionRequired($task, $user_id) )
            $failed_requirement->connection_fail = 'Connection Required';

        if( !static::checkTaskFollowerRequired($task->id, $task->user_id, $user_id) )
            $failed_requirement->follower_fail = 'Follower Required';

        if( !static::checkTaskReputationRequired($task->id, $user_id) )
            $failed_requirement->reputation_fail = 'Reputation Required';

        if( !static::checkTaskActivityRequired($task->id, $user_id) )
            $failed_requirement->activity_fail = 'Activity Required';

        if( static::checkTaskAttachmentRequired($task->id) )
            $failed_requirement->attachment_fail = 'Attachment Required';

        $count = (new \ArrayObject($failed_requirement))->count();
        if( $count > 0 )
            return  $failed_requirement;
        return false || null;
    }

    protected static function _revokeUser(int $user_id, int $completer_user_id, int $task_id, string $reason) {
        $task = Task::where(function($query) use ($task_id) {
            $query->where('id', $task_id)
                ->where('status', (bool) 1)
                ->where('expired_date', '>=', app('carbon')->now());
        })->first();
        
        if( $task ) {
            $completer = User::find($completer_user_id);
            $task_creator = User::find($user_id);
            if( static::checkCanRevoke($completer->id, $task->id) ) {
                if( static::revokeUserTask([
                    'completer_bank' => $completer->bank,
                    'task_creator_bank' => $task_creator->bank,
                    'reward' => $task->reward,
                    'completer_id' => $completer->id,
                    'task_id' => $task->id,
                    'reason' => $reason
                ]) ) return true;
            }
        }
        return false;
    }

    protected static function revokeUserTask(array $data) : bool {
        if( isset($data) ) {
            $reward = $data['reward'];
            $data['completer_bank']->balance -= $reward;
            if( $data['completer_bank']->save() ) {
                $data['task_creator_bank']->balance += $reward;
                if( $data['task_creator_bank']->save() ) {
                    if( (new BannedUserTask())->bannedUserFromTask([ 'user_id' => $data['completer_id'], 'task_id' => $data['task_id'], 'reason' => $data['reason'] ]) ) {
                        $task_user = TaskUser::where(function($query) use (& $data) {
                            $query->where('user_id', $data['completer_id'])
                                ->where('task_id', $data['task_id'])
                                ->where('revoke', (bool) 0);
                        })->first();
                        if( $task_user ) {
                            $task_user->revoke = 1;
                            if( $task_user->save() ) {
                                $revoked_task = Task::find($data['task_id']);
                                if( $revoked_task ) {
                                    $revoked_task->final_cost += $data['reward'];
                                    $revoked_task->total_rewards -= 1;
                                    if( $revoked_task->save() ) {
                                        $user_completer = User::where(function($query) use ($data) {
                                            $query->select(['id', 'name', 'username'])
                                                ->where('id', $data['completer_id']);
                                        })->first();
                                        $history = ($user_completer['name'] ? $user_completer['name'] : $user_completer['username']) . ' was revoked from this task!';
                                        if( (new TaskTransactionHistory())->saveData([
                                            'task_id' => $data['task_id'],
                                            'user_id' => $data['completer_id'],
                                            'type' => 'revoked',
                                            'history' => $history
                                        ]) ) return true;
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
        return false;
    }

    protected static function _blockUserFromTask(array $data) : bool {

        if( static::checkCanRevoke($data['completer_id'], $data['task_id']) ) {
            $task = Task::find($data['task_id']);
            $owner = User::find($data['user_id']);
            $completer = User::find($data['completer_id']);
            $reward = $task->reward;
            $owner_bank = $owner->bank;
            $completer_bank = $completer->bank;

            $completer_bank->balance -= $reward;
            if( $completer_bank->save() ) {

                $owner_bank->balance += $reward;
                if($owner_bank->save()) {
                    if( (new BannedUserTask())->bannedUserFromTask(['user_id' => $completer->id, 'task_id' => $task->id, 'reason' => ""]) ) {
                        $completer_id = $completer->id;
                        $task_id = $task->id;
                        $task_user = TaskUser::where(function($query) use ( $completer_id, $task_id ) {
                            $query->where('user_id', $completer_id)
                                ->where('task_id', $task_id)
                                ->where('revoke', (bool) 0);
                        })->first();
                        if( $task_user ) {
                            $task_user->revoke = 1;
                            if( $task_user->save() ) {

                                $revoked_task = Task::find($task_id);
                                if( $revoked_task ) {
                                    $revoked_task->final_cost += $reward;
                                    $revoked_task->total_rewards -= 1;
                                    if( $revoked_task->save() ) {
                                        $history = ($completer->name ? $completer->name : $completer->username) . ' was revoked from this task!';
                                        if( (new TaskTransactionHistory())->saveData([
                                            'task_id' => $task_id,
                                            'user_id' => $completer_id,
                                            'type' => 'revoked',
                                            'history' => $history
                                        ]) ) {

                                            #get all the active task of task creator
                                            $tasks = Task::where(function($query) use (& $data) {
                                                $query->where('status', (bool) 1)
                                                    ->where('expired_date', '>=', app('carbon')->now())
                                                    ->where('user_id', $data['user_id'])
                                                    ->where('id', '<>', $data['task_id']);
                                            })->get();

                                            $tasks->each(function($item) use (& $data, $owner_bank, $completer_bank, $owner)  {
                                                $completed = TaskUser::where('user_id', $data['completer_id'])
                                                                    ->where('task_id', $item->id)
                                                                    ->where('revoke', (bool) 1)
                                                                    ->first();
                                                if( $completed ) {
                                                    $start = app('carbon')->now();
                                                    $end = $completed->created_at;
                                                    $due = $start->diffInDays($end);
                                                    if( $due < (int) 14) {

                                                        $completer_bank->balance -= $completed->reward;
                                                        if( $completer_bank->save() ) {
                                                            $owner_bank->balance += $completed->reward;
                                                            if($owner_bank->save()) {
                                                                if( (new BannedUserTask())->bannedUserFromTask(['user_id' => $completed->user_id, 'task_id' => $completed->task_id, 'reason' => ""]) ) {
                                                                    $completed->revoke = 1;
                                                                    if( $completed->save() ) {
                                                                        $revoked_task = Task::find($completed->task_id);
                                                                        if( $revoked_task ) {
                                                                            $revoked_task->final_cost += $completed->reward;
                                                                            $revoked_task->total_rewards -= 1;
                                                                            if( $revoked_task->save() ) {
                                                                                $history = ($owner->name ? $owner->name : $owner->username) . ' was revoked from this task!';
                                                                                (new TaskTransactionHistory())->saveData([
                                                                                    'task_id' => $completed->task_id,
                                                                                    'user_id' => $completed->user_id,
                                                                                    'type' => 'revoked',
                                                                                    'history' => $history
                                                                                ]);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                    }
                                                }
                                            });

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if( (new BlockUserTask())->saveData(['task_user_id' => $data['user_id'], 'completer_id' => $data['completer_id'] ]) )
                return true;

        }
        return false;
    }

    protected static function checkCanRevoke(int $user_id, int $task_id) : bool {
        $can_revoke = TaskUser::where('user_id', $user_id)->where('task_id', $task_id)->where('revoke', 0)->first();
        if($can_revoke) {
            $start = app('carbon')->now();
            $end = $can_revoke->created_at;
            $due = $start->diffInDays($end);
            if( $due < (int) 14)
                return true;
        }
        return true;
    }
}