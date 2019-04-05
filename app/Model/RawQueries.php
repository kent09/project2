<?php

namespace App\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Model\TaskUser;
use App\Model\Task;
use App\User;
use App\Traits\Manager\UserTrait;
use App\Traits\TaskTrait;
use App\Model\FollowerTask;
use App\Model\ConnectionTask;
use Carbon\Carbon;
class RawQueries 
{
    use UserTrait, TaskTrait;

    public function activeTask($req){
        $user = Auth::user();

        $search_key = $req->has('search_key') ? $req->search_key : "";
        $filter = $req->has('filter') ? $req->filter : ""; 
        $offset = $req->has('offset') ? $req->offset : 0; 
        $limit = $req->has('limit') ? $req->limit : 10; 


        $category_query = "";
        $search_query = "";

        if($filter <> ''){
            $category_query = " AND t.category = '".$filter."'";
        }

        if($search_key <> ''){
            $search_query = ' AND (t.title LIKE "%'.$search_key.'%" OR t.slug LIKE "%'.$search_key.'%" OR u.name LIKE "%'.$search_key.'%")';
        }

        $tasks_query = 'SELECT CONCAT( u.id, ",", u.name, ",", u.username, ",", u.has_avatar) AS user_info,
                        t.id, t.title, t.slug, t.reward, t.user_id, t.created_at,
                        t.description, t.task_link, t.category,
                        t.expired_date, t.total_point, t.total_rewards,
                        IF((SELECT id FROM user_followers WHERE user_id = t.`user_id` AND follower_id = "'.$user->id.'") IS NULL, false,true) AS is_follower,
                        false AS is_high_risk,
                        (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) as available_completer
                        FROM tasks AS t LEFT JOIN users AS u ON u.id = t.`user_id`
                        WHERE t.`status` = 1 
                        AND t.id NOT IN (SELECT task_id FROM task_hiddens WHERE user_id =  "'.$user->id.'" AND hidden = 1) 
                        AND t.id NOT IN (SELECT tu.task_id FROM task_user AS tu INNER JOIN tasks AS tas
                            ON tu.`task_id` = tas.`id` WHERE tu.user_id =  "'.$user->id.'" AND tas.approved = 1)
                        AND t.id NOT IN (SELECT task_id FROM banned_user_task WHERE user_id =  "'.$user->id.'" AND revoked = 1)
                        AND t.`user_id` NOT IN (SELECT task_user_id FROM block_user_tasks WHERE user_id =  "'.$user->id.'" AND block = 1)
                        AND t.`user_id` NOT IN (SELECT user_id  FROM block_user_tasks WHERE task_user_id =  "'.$user->id.'" AND block = 1)
                        '.$category_query.$search_query.'
                        AND t.user_id <>  "'.$user->id.'" AND t.`expired_date` >= NOW() AND t.`final_cost` <> 0 
                        AND (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) > 0
                        GROUP BY t.user_id ORDER BY t.reward DESC';
        
        $tasks_count = count(DB::select(DB::raw($tasks_query)));
        $tasks = DB::select(DB::raw($tasks_query.' LIMIT '.$offset.','.$limit.''));

        $tasks = array_map(function($task) use ($user) {
                $task_model = Task::find($task->id);

                $follower_required = FollowerTask::where(function($query) use ($task) {
                    $query->where('task_id', $task->id)
                        ->where('task_user_id', $task->user_id)
                        ->where('active', (bool) 1);
                })->first();

                $connection_required = ConnectionTask::where(function($query) use ($task) {
                    $query->where('task_id', $task->id)
                        ->where('status', (bool) 1);
                })->first();

                $reputation_required = ReputationTask::where(function ($query) use ($task) {
                    $query->where('task_id', $task->id)
                        ->where('active', (bool) 1);
                })->first();

                $is_follower_required = false;
                if($follower_required <> null){
                    $is_follower_required = ($task->is_follower == 1) ? false : true;
                }

                $is_connection_required = false;
                if($connection_required <> null){
                    $is_connection_required =( static::checkTaskConnectionRequired($task_model,$user->id)) ? false : true;
                }

                $is_reputation_passed = true;  
                if($reputation_required <> null){
                    $is_reputation_passed = $this->isReputationPassed($task->id,$user->id);
                }   

                $is_activity_passed = true;
                if($reputation_required <> null){
                    $is_activity_passed = $this->isActivityPassed($task->id,$user->id);
                }   

                $attachment_required = false;
                if( static::checkTaskAttachmentRequired($task->id) ){
                    $attachment_required = true;
                }
                
                $task->is_high_risk = $this->isHighRisk($task->user_id);
                $task->is_reputation_passed = $is_reputation_passed;
                $task->is_activity_passed = $is_activity_passed;
                $task->is_follower = ($task->is_follower == 1) ? true : false;
                $task->follower_required = $is_follower_required;
                $task->connection_required = $is_connection_required;
                $task->attachment_required = $attachment_required;

                if($task->slug == null){
                    static::urlSlugGenerator($task->title,$task->id);
                    $task->slug = Task::find($task->id)->slug;
                }
                return $task;
        },$tasks);

        return ['task' => $tasks, 'count' => $tasks_count];
    }

    public function hiddenTask($req){
        $user = Auth::user();

        $search_key = $req->has('search_key') ? $req->search_key : "";
        $filter = $req->has('filter') ? $req->filter : ""; 
        $offset = $req->has('offset') ? $req->offset : 0; 
        $limit = $req->has('limit') ? $req->limit : 10; 

        $category_query = "";
        $search_query = "";

        if($filter <> ''){
            $category_query = " AND t.category = '".$filter."'";
        }

        if($search_key <> ''){
            $search_query = ' AND (t.title LIKE "%'.$search_key.'%" OR t.slug LIKE "%'.$search_key.'%" OR u.name LIKE "%'.$search_key.'%")';
        }

        $task_query = 'SELECT
                            u.`name`, u.`email`, u.`username`, h.task_id,
                            t.title, t.slug, t.reward, t.user_id, t.created_at,
                            t.description, t.task_link, t.category,
                            t.expired_date, t.total_point, t.total_rewards,
                            IF((SELECT id FROM user_followers WHERE user_id = t.`user_id` AND follower_id = "'.$user->id.'") IS NULL, false,true) AS is_follower,
                            (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) as available_completer
                        FROM
                            task_hiddens AS h
                            LEFT JOIN tasks AS t ON t.id = h.`task_id`
                            LEFT JOIN users AS u ON u.`id` = t.`user_id`
                        WHERE h.`user_id` = "'.$user->id.'"
                            AND h.`hidden` = 1
                            '.$category_query.$search_query.'
                        ORDER BY h.`id` DESC';
        
        $tasks = DB::select(DB::raw($task_query.' LIMIT '.$offset.','.$limit.''));

        $tasks = array_map(function($task) use ($user) {
            $task->is_high_risk = $this->isHighRisk($task->user_id);
            $task->is_reputation_passed = $this->isReputationPassed($task->task_id,$user->id);
            $task->is_activity_passed = $this->isActivityPassed($task->task_id,$user->id);
            $task->is_follower = ($task->is_follower == 1) ? true : false;
            // $task->avatar = User::find($task->user_id)->avatar;
            if($task->slug == null){
                static::urlSlugGenerator($task->title,$task->task_id);
                $task->slug = Task::find($task->task_id)->slug;
            }
            return $task;
        },$tasks);
        
        return $tasks;
    }

    public function ownTask($req){
        $user = Auth::user();

        $search_key = $req->has('search_key') ? $req->search_key : "";
        $filter = $req->has('filter') ? $req->filter : ""; 
        $offset = $req->has('offset') ? $req->offset : 0; 
        $limit = $req->has('limit') ? $req->limit : 10; 

        $category_query = "";
        $search_query = "";

        if($filter <> ''){
            $category_query = " AND t.category = '".$filter."'";
        }

        if($search_key <> ''){
            $search_query = ' AND (t.title LIKE "%'.$search_key.'%" OR t.slug LIKE "%'.$search_key.'%")';
        }

        $task_query = 'SELECT t.id as task_id, t.title, t.slug, t.reward, t.user_id, t.created_at,
                            t.description, t.task_link, t.category, t.final_cost,
                            t.expired_date, t.total_point, t.total_rewards, t.status,
                            IF((SELECT id FROM user_followers WHERE user_id = t.`user_id` AND follower_id = "'.$user->id.'") IS NULL, false,true) AS is_follower,
                            (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) as available_completer
                        FROM
                            tasks AS t
                        WHERE t.`user_id` = "'.$user->id.'"
                        AND t.`id` NOT IN (SELECT task_id FROM task_deleteds WHERE status = 1)
                        '.$category_query.$search_query.'
                        ORDER BY t.`created_at` DESC';
        
        $tasks = DB::select(DB::raw($task_query.' LIMIT '.$offset.','.$limit.''));

        $tasks = array_map(function($task) use ($user) {
            $task->is_high_risk = $this->isHighRisk($task->user_id);
            $task->is_reputation_passed = $this->isReputationPassed($task->task_id,$user->id);
            $task->is_activity_passed = $this->isActivityPassed($task->task_id,$user->id);
            $task->is_follower = ($task->is_follower == 1) ? true : false;
            // $task->avatar = User::find($task->user_id)->avatar;
            if($task->slug == null){
                static::urlSlugGenerator($task->title,$task->task_id);
                $task->slug = Task::find($task->task_id)->slug;
            }
            $task->status_str = $this->getTaskStatus($task);
            return $task;
        },$tasks);
        
        return $tasks;
    }

    public function completedTask($req){
        $user = Auth::user();

        $search_key = $req->has('search_key') ? $req->search_key : "";
        $filter = $req->has('filter') ? $req->filter : ""; 
        $offset = $req->has('offset') ? $req->offset : 0; 
        $limit = $req->has('limit') ? $req->limit : 10; 

        $category_query = "";
        $search_query = "";

        if($filter <> ''){
            $category_query = " AND t.category = '".$filter."'";
        }

        if($search_key <> ''){
            $search_query = ' AND (t.title LIKE "%'.$search_key.'%" OR t.slug LIKE "%'.$search_key.'%" OR u.name LIKE "%'.$search_key.'%")';
        }

        $task_query = 'SELECT
                            t.title, t.slug, t.reward, t.created_at,
                            t.description, t.task_link, t.category, t.final_cost,
                            t.expired_date, t.total_point, t.total_rewards, t.status,
                            u.name, u.username, tu.`task_id`, tu.`user_id`, t.user_id as creator_id,
                            IF((SELECT id FROM user_followers WHERE user_id = t.`user_id` AND follower_id = "'.$user->id.'") IS NULL, false,true) AS is_follower,
                            false AS is_high_risk,
                            (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) as available_completer
                        FROM
                            task_user AS tu
                            LEFT JOIN tasks AS t ON tu.`task_id` = t.`id`
                            LEFT JOIN users AS u ON u.`id` = tu.`task_creator`
                        WHERE tu.`user_id` = "'.$user->id.'" 
                        '.$category_query.$search_query.'
                        ORDER BY tu.`created_at` DESC';

        $tasks = DB::select(DB::raw($task_query.' LIMIT '.$offset.','.$limit.''));

        $tasks = array_map(function($task) use ($user){
            $task->is_high_risk = $this->isHighRisk($task->user_id);
            $task->is_reputation_passed = $this->isReputationPassed($task->task_id,$user->id);
            $task->is_activity_passed = $this->isActivityPassed($task->task_id,$user->id);
            $task->is_follower = ($task->is_follower == 1) ? true : false;
            // $task->avatar = User::find($task->user_id)->avatar;
            if($task->slug == null){
                static::urlSlugGenerator($task->title,$task->task_id);
                $task->slug = Task::find($task->task_id)->slug;
            }
            return $task;
        },$tasks);
        
        return $tasks;
    }

    public function countActiveTask(){

        $user = Auth::user();

        $tasks_query = 'SELECT t.*
                        FROM tasks AS t LEFT JOIN users AS u ON u.id = t.`user_id`
                        WHERE t.`status` = 1 
                        AND t.id NOT IN (SELECT task_id FROM task_hiddens WHERE user_id =  "'.$user->id.'" AND hidden = 1) 
                        AND t.id NOT IN (SELECT tu.task_id FROM task_user AS tu INNER JOIN tasks AS tas
                            ON tu.`task_id` = tas.`id` WHERE tu.user_id =  "'.$user->id.'" AND tas.approved = 1)
                        AND t.id NOT IN (SELECT task_id FROM banned_user_task WHERE user_id =  "'.$user->id.'" AND revoked = 1)
                        AND t.`user_id` NOT IN (SELECT task_user_id FROM block_user_tasks WHERE user_id =  "'.$user->id.'" AND block = 1)
                        AND t.`user_id` NOT IN (SELECT user_id  FROM block_user_tasks WHERE task_user_id =  "'.$user->id.'" AND block = 1)
                        AND t.user_id <>  "'.$user->id.'" AND t.`expired_date` >= NOW() AND t.`final_cost` <> 0 
                        HAVING (IFNULL(t.`total_point`,0) - IFNULL(t.`total_rewards`, 0)) > 0
                        ORDER BY t.reward DESC';
        
        $tasks = DB::select(DB::raw($tasks_query));

        return count($tasks);
    }

    public function countHiddenTask(){
        $user = Auth::user();

        $task_query = 'SELECT
                        t.*
                    FROM
                        task_hiddens AS h
                        LEFT JOIN tasks AS t ON t.id = h.`task_id`
                    WHERE h.`user_id` = "'.$user->id.'"
                        AND h.`hidden` = 1';

        $tasks = DB::select(DB::raw($task_query));

        return count($tasks);
    }

    public function countOwnTask(){
        $user = Auth::user();

        $task_query = 'SELECT
                        t.*
                    FROM
                        tasks AS t
                    WHERE t.`user_id` = "'.$user->id.'"
                    AND t.`id` NOT IN (SELECT task_id FROM task_deleteds WHERE status = 1)';

        $tasks = DB::select(DB::raw($task_query));

        return count($tasks);

    }

    public function countCompletedTask(){
        $user = Auth::user();

        $task_query = 'SELECT
                        tu.*
                    FROM
                        task_user AS tu
                    WHERE tu.`user_id` = "'.$user->id.'"';

        
        $tasks = DB::select(DB::raw($task_query));

        return count($tasks);
    }

    public function userChatList($req){
        $user_id = Auth::id();
        $limit = $req->has('limit') ? $req->limit : 10;
        $offset = $req->has('offset') ? $req->offset : 0;

        $chat_query = 'SELECT vc.user_id, u.name, u.username, IF(vc.status=1,"online","offline") AS status
                        FROM visitor_counters AS vc 
                        LEFT JOIN users as u ON u.id = vc.user_id
                        WHERE vc.user_id IN (SELECT user_id FROM user_followers WHERE follower_id = :follower_id
                        AND user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :blocker_id AND status = 1))
                        GROUP BY vc.user_id ORDER BY vc.updated_at DESC';
        
        $chats = DB::select(DB::raw($chat_query.' LIMIT '.$offset.','.$limit.''),array('follower_id' => $user_id, 'blocker_id' => $user_id));
        
        $chats = array_map(function($chat) {
                // $chat->avatar = User::find($chat->user_id)->avatar;
                $chat->unread = 0;
                $chat->mood = null;
                return $chat;
        },$chats);

        return $chats;
    }

    public function isReputationPassed($task_id, $task_user){
        $query = 'SELECT
                    IF(COUNT(rt.id) = 0,TRUE,
                    IF(IFNULL(rt.`reputation`, 0) <= IFNULL(uras.`reputation`, 0),TRUE,FALSE)) AS is_reputation_passed
                FROM
                    reputation_tasks AS rt
                    LEFT JOIN tasks AS t
                    ON t.id = rt.`task_id`
                    AND rt.`active` = 1
                    LEFT JOIN user_reputation_activity_scores AS uras
                    ON uras.`user_id` = :taskUser
                WHERE rt.task_id = :taskId';

        $res = DB::select(DB::raw($query),
                    array('taskUser' => $task_user, 'taskId' => $task_id)
                );

        return ($res[0]->is_reputation_passed == 1) ? true : false;
    }

    public function isHighRisk($user_id){
        $task_query = TaskUser::query();
        $task_count = $task_query->where('task_creator', $user_id)->count();
        $task_revoked_count = $task_query->where('task_creator',$user_id)->where('revoke',1)->count();
        $avg = ($task_count > 0) ? ($task_revoked_count / $task_count) * 100 : 0;
        return ($avg >= 50);
    }

    public function isActivityPassed($task_id, $task_user){
        $query = 'SELECT
                    IF(COUNT(ast.id) = 0,TRUE,
                    IF(IFNULL(ast.`activity_score`, 0) <= IFNULL(uras.`activity_score`, 0),TRUE,FALSE)) AS is_activity_passed
                FROM
                    activity_score_tasks AS ast
                    LEFT JOIN tasks AS t ON t.id = ast.`task_id` AND ast.`active` = 1
                    LEFT JOIN user_reputation_activity_scores AS uras ON uras.`user_id` = :taskUser
                WHERE ast.`task_id` = :taskId';

        $res = DB::select(DB::raw($query),
                    array('taskUser' => $task_user, 'taskId' => $task_id)
                );

        return ($res[0]->is_activity_passed == 1) ? true : false;
    }

    public function getTaskStatus($task){
        $status = "active";
        $status = ($task->expired_date <= Carbon::now()) ? 'expired' : $status;
        $status = ($task->final_cost == 0) ? 'completed' : $status;
        $status = ($task->status == 0) ? 'deactivated' : $status;

        return $status;
    }

}
