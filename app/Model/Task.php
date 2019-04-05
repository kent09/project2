<?php

namespace App\Model;

use App\Model\UserReputationActivityScore;
use App\Model\ActivityScoreTask;
use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Model\ReputationTask;
use App\Model\TaskUser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Task extends Model
{
    const TRANSACTION_TYPE = [0 => 'completion', 1 => 'revoked', 2 => 'gift-coin'];
    //
    protected $table = 'tasks';

    protected $appends = ['user_info','status_str','available_completer'];

    public function getAvailableCompleterAttribute(){
        return $this->attributes['total_point'] - $this->attributes['total_rewards'];
    }
    public function getStatusStrAttribute(){
        $status = "active";
       
        $status = ($this->attributes['expired_date'] <= Carbon::now()) ? 'expired' : $status;
        $status = ($this->attributes['final_cost'] == 0) ? 'completed' : $status;
        $status = ($this->attributes['status'] == 0) ? 'deactivated' : $status;
        $deleted = \App\Model\TaskDeleted::where('task_id', $this->attributes['id'])->where('status', 1)->first();
        if ($deleted !== null) {
            $status = 'deleted';
        }

        return $status;
    }
    public function getUserInfoAttribute(){
        $user = User::find($this->attributes['user_id']);
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'has_avatar' => $user->has_avatar,
        ];
    }
    
    // public function getRequirementStatusAttribute(){
    //     $user_id = $this->attributes['user_id'];
    //     $task_id = $this->attributes['id'];

    //     $requirement = array();
    //     $user_activity_reputation = UserReputationActivityScore::where('user_id', $user_id)->first();
      
    //     $activity_query = ActivityScoreTask::query();
    //     $reputation_query = ReputationTask::query();
    //     $task_query = TaskUser::query();

    //     $requirement['is_activity_passed'] = false;
    //     $requirement['is_reputation_passed'] = false;
    //     $requirement['is_follower'] = false;

    //     $activity_score = $activity_query->where('task_id', $task_id)
    //         ->where('active',1);
    //     $reputation_score = $reputation_query->where('task_id', $task_id)
    //         ->where('active',1);
     
    //     $task_count = $task_query->where('task_creator', $user_id)->count();

    //     $task_revoked_count = $task_query->where('task_creator',$user_id)->where('revoke',1)->count();
    //     $avg = ($task_count > 0) ? ($task_revoked_count / $task_count) * 100 : 0;
        
       
    //     if($user_activity_reputation){
    //         $requirement['is_reputation_passed'] = ($reputation_score->count() == 0) ? true :
    //             $reputation_query->where('reputation','<=', $user_activity_reputation->reputation)->count() ? true : false;
    //         $requirement['is_activity_passed'] = ($activity_score->count() == 0) ? true :
    //             $activity_score->where('activity_score','>=', $user_activity_reputation->activity_score)->count() ? true : false;
    //         $requirement['is_follower'] = Auth::user() ? $this->isFollowed($user_id, Auth::id()) : false;
    //     }
    //     else{
    //         $requirement['is_reputation_passed'] = false;
    //         $requirement['is_activity_passed'] = false;
    //         $requirement['is_follower'] = false;
    //     }

    //     $requirement['is_high_risk'] = ($avg >= 50);

    //     return $requirement;
    // }
    private function isFollowed(int $task_user_id, int $user_id) : bool {
        $follower = UserFollower::where(function ($query) use ($user_id, $task_user_id) {
                        $query->where('user_id', $task_user_id)
                            ->where('follower_id', $user_id)
                            ->where('status', (bool) 1);
                    })->first();

        if( $follower )
            return true;
        return false;
    }

    #relation
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function completerTask() {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')->withPivot( ['user_id']);
    }

    public function completer(){
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')->withPivot( ['approved','created_at']);
    }

    public function attachment() {
        return $this->hasMany(TaskCompletionDetail::class, 'task_id');
    }

    public function activityScoreTask() {
        return $this->hasOne(ActivityScoreTask::class, 'task_id')->select(['task_id', 'activity_score']);
    }

    public function reputationTask() {
        return $this->hasOne(ReputationTask::class, 'task_id')->select(['task_id', 'reputation']);
    }

    public function followerOption() {
        return $this->hasOne(FollowerTask::class, 'task_id')->select(['task_id', 'active']);
    }

    public function attachmentOption() {
        return $this->hasOne(TaskOptionDetail::class, 'task_id')->select(['task_id', 'status']);
    }

    public function connectionOption() {
        return $this->hasOne(ConnectionTask::class, 'task_id')->select(['task_id', 'status']);
    }
}
