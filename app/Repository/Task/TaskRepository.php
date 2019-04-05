<?php

namespace App\Repository\Task;

use App\User;
use Carbon\Carbon;
use App\Model\Task;
use App\Model\TaskUser;
use App\Model\RawQueries;
use App\Model\TaskHidden;
use App\Model\TaskWizard;
use App\Traits\TaskTrait;
use App\Model\BlockedUser;
use App\Model\TaskDeleted;
use App\Model\FollowerTask;
use App\Model\UserFollower;
use App\Model\UserFreeTask;
use App\Model\BlockUserTask;
use App\Traits\UtilityTrait;
use App\Model\ConnectionTask;
use App\Model\ReputationTask;
use App\Model\TaskOptionDetail;
use App\Model\ActivityScoreTask;
use App\Traits\Manager\UserTrait;
use App\Events\NewTaskTransaction;
use Illuminate\Support\Facades\DB;
use App\Model\KryptoniaTaskComment;
use App\Model\TaskCompletionDetail;
use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Contracts\Task\TaskInterface;
use App\Model\TaskTransactionHistory;
use Intervention\Image\Facades\Image;
use App\Model\KryptoniaTaskSubComment;
use App\Model\KryptoniaTaskCommentDetail;
use Illuminate\Support\Facades\Validator;
use App\Model\KryptoniaTaskSubCommentDetail;

class TaskRepository implements TaskInterface
{
    use TaskTrait, UtilityTrait, UserTrait;
    protected $query, $category;
    protected $limit = 10;
    protected $free_task_reward = 100;

    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($request) {
        // TODO: Implement index() method.
        $tasks = $this->listOfActiveTask($request);

       if(count($tasks['task']) > 0)
            return static::response(null, static::responseJwtEncoder($tasks), 201, 'success');
       return static::response('No Data Fetched', null, 200, 'success');
    }

    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ownTask($request) {
        // TODO: Implement ownTask() method.
        $tasks = $this->listOfAllOwnTask($request);
        if(count($tasks['task']) > 0)
            return static::response('Successfully Load Hidden Tasks', static::responseJwtEncoder($tasks), 201, 'success');
        return static::response('No Data Fetched', null, 401, 'error');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hiddenTask($request) {
        // TODO: Implement hiddenTask() method.
        $tasks = $this->listOfHiddenTask($request);
        if(count($tasks['task']) > 0)
            return static::response('Successfully Load Completed Tasks', static::responseJwtEncoder($tasks), 200, 'success');
        return static::response('No Data Fetched', null, 401, 'error');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedTask($request) {
        // TODO: Implement completedTask() method.
        $tasks = $this->listOfCompletedTask($request);

        // if(count($tasks['task']) > 0)
            return static::response(null, static::responseJwtEncoder($tasks), 201, 'success');
        // return static::response('No Data Fetched', null, 200, 'success');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask($request) {

        // TODO: Implement createTask() method.
        $error_message = []; # error container.

        # LIMITAIONS START
        $limitation = is_limitation_passed('task-creation-per-day');
        if (!$limitation['passed']) {
            return static::response('Forbidden at limitation!', null, 403, 'error');
        }
        if ($limitation['role'] !== 'admin') {
            $data = $limitation['data'];
            $now = Carbon::now()->toDateString();
            $created_count = Task::where('user_id', Auth::id())->where('created_at', 'LIKE', $now . '%')->count();
            if($data->value <> 0){
                if ($created_count >= $data->value) {
                    return static::response('Forbidden at limitation! Cannot create more than '.$limitation['data']->value .' tasks per day!', null, 403, 'error');
                }
            }
        }

        $fee_charge = static::taskFeeCharge();
        $free_task_option = $request->has('free_task_option') ? $request->free_task_option : false;
        $user_limitations = static::userAccessLimitations();
        $free_task_limit = $user_limitations['free_task'];
        # LIMITATIONS END

        $task = new Task;
        $id = Auth::id();
        $checkUser = User::find($id);

        if($checkUser->ban > 0){
            array_push($error_message, 'Error, your account is banned!');
        }

        if($checkUser->status == 0){
            array_push($error_message, 'Error, your account is disabled!');
        }

        if($checkUser->verified == 0){
            array_push($error_message, 'Error, please verify your email address!');
        }

        if($checkUser->agreed == 0){
            array_push($error_message, 'Error,  your account is unconfirmed!');
        }

        $holdings = (new WalletRepository())->getholdings($id, true);

        $pending = $this->checkPendingWithdrawal($id);
        if($pending)
            array_push($error_message, 'Error, Please verify your pending withdrawal!');

        if( $request->from_wizard ) {
            $available = $request->from_wizard ? (int) 100 : $holdings['available'];
        } else {
            $available = $holdings['available'];
        }

        if( !is_numeric( $request->reward ) )
            array_push($error_message, 'Error, task reward format is invalid!');

        if( !is_numeric( $request->total_completer ) )
            array_push($error_message, 'Error, total completer format is invalid!');

        $allotted = 0;
        if( is_numeric($request->reward) AND is_numeric($request->total_completer) ) {
            $allotted = $request->reward * $request->total_completer;
        }

        #check for further validation.
        if( $request->from_wizard ) {
            if($available < $allotted)
                array_push($error_message, 'Error, Insufficient SUP!');
        }

        if( !$request->task_category )
            array_push($error_message,'Error, task category is required!');

        if( $request->total_completer === 0 )
            array_push($error_message, 'Error, please enter a valid amount or number!' );

        if( $request->reward === 0 )
            array_push($error_message, 'Error, please enter a valid amount or number!');

        if( !$request->title )
            array_push($error_message, 'Error, Title is required!');

        if( !$request->description )
            array_push($error_message, 'Error, Description is required!' );

        if( !$request->task_link )
            array_push($error_message, 'Error, Task link is required!');

        if( !static::urlValidator($request->task_link) )
            array_push($error_message, 'Error, Task link is invalid!'  );

        if( $request->reputation_option ) {
            if( is_null($request->reputation) AND is_null($request->activity_score) )
                array_push($error_message, 'Error, reputation or activity score is require!' );

            if( $request->reputation > 100 )
                array_push($error_message, 'Error, Reputation must be equal or less than to 100 but not equal to zero');
        }

        if( count($error_message) > 0 )
            return static::response('', $error_message, 401, 'error');

        # START FREE TASK CHECKING
        $remain_charge = 0;
        $free_task_applied = 0;
        $free_task_fee = 0;
        $free_total_budget = 0;
        $remain_completer = 0;
        $is_free_task = 0;
        if($free_task_option && $free_task_limit > 0){
            $is_free_task = 1;
            if($request->total_completer > $free_task_limit){
                $remain_completer = $request->total_completer - $free_task_limit;
                $free_task_applied = $free_task_limit;
                $free_total_budget = $free_task_applied * $this->free_task_reward;
                $free_task_fee = ($fee_charge <> 0) ? ($free_total_budget * $fee_charge) : 0;
                $remain_charge = $remain_completer * $this->free_task_reward;
                $fee_charge = ($fee_charge <> 0) ? ($allotted * $fee_charge) : 0;
            }else{
                $remain_charge = 0;
                $free_task_applied = $request->total_completer;
                $free_total_budget = $free_task_applied * $this->free_task_reward;
                $free_task_fee = ($fee_charge <> 0) ? ($free_total_budget * $fee_charge) : 0;
                $fee_charge = $free_task_fee;
            }
        }else{
            $fee_charge = ($fee_charge <> 0) ? ($allotted * $fee_charge) : 0;
            $remain_charge = $allotted;
        }
        # END FREE START CHECKING

        #persist
        $alloted_with_charge =  $remain_charge + $fee_charge;
        if( $available >= $alloted_with_charge ) {

            $task->title = $request->title;
            $task->description = $request->description;
            $task->task_link = $request->task_link;
            $task->total_point = $request->total_completer;
            $task->reward = $request->reward;
            $task->user_id = $id;
            $task->final_cost = $allotted;
            $task->category = $request->task_category;
            $task->expired_date = Carbon::now()->addDays(5);
            $task->status = 1;
            $task->total_rewards = null;
            $task->fee_charge = $fee_charge;
            $task->is_free_task = $is_free_task;

            if( $request->from_wizard )
                $task->status = (bool) 0;

            if( $request->task_image ) {

                $file = $request->task_image;
                
                DB::beginTransaction();
                try {
                    if( $task->save() ) {
                        #validate image type
                        if( !in_array($request->image_format, ['jpg', 'jpeg', 'png', 'gif']) )
                            array_push($error_message, 'Task Image Is Not Supported!');

                        #task associate
                        if( $request->follower_option )
                            if( !(new FollowerTask)->saveFollowerTask($id, $task->id) )
                                array_push($error_message,'Error while saving task follower option!');

                        if( $request->reputation_option )
                            if( !(new ReputationTask)->saveReputationTask($task->id, $request->reputation) )
                                array_push($error_message, 'Error while saving task reputation option!');

                        if( $request->reputation_option )
                            if( !(new ActivityScoreTask)->saveActivityScoreTask($task->id, $request->activity_score) )
                                array_push($error_message, 'Error while saving task activity score option!');

                        if( $request->connection_option )
                            if( !(new ConnectionTask())->saveConnectionTask($task->id, $request->connection_option) )
                                array_push($error_message, 'Error while saving task connection option');

                        if( $request->task_completion_attachment_option )
                            if( !(new TaskOptionDetail)->saveTaskOptionDetail($task->id, $request->task_completion_attachment_option) )
                                array_push($error_message, 'Error while saving task attachment option');

                         # START SAVING FREE TASK 
                        if($free_task_option && $free_task_limit > 0){
                            $free_task_mod = new UserFreeTask();
                            $free_task_mod->task_id = $task->id;   
                            $free_task_mod->user_id = $id;
                            $free_task_mod->role_id = $checkUser->role()->id;
                            $free_task_mod->completer_cnt = $free_task_applied;
                            $free_task_mod->task_reward = $this->free_task_reward;
                            $free_task_mod->task_fee = $free_task_fee;
                            $free_task_mod->total_budget = $free_total_budget;
                            if(!$free_task_mod->save()){
                                 array_push($error_message, 'Error while saving free task transaction!');
                            }
                        }
                        # END SAVING FREE TASK 

                        #task coming from wizard
                        if( $request->from_wizard )
                            if( ! (new TaskWizard)->saveTaskFromWizard($task->id, $id) ) {
                                DB::rollBack();
                                return static::failedTaskWizard($task->id);
                            }

                        if( count($error_message) > 0 ) {
                            DB::rollBack();
                            return static::response('Error', $error_message, 401, 'error');
                        } else {
                            
                            #upload task image
                            $format = $request->image_format;
                            if( static::uploadTaskImage($file, $task->id, $format, 'task',"") ) {
                                #save slug
                                static::urlSlugGenerator($request->title, $task->id);

                                DB::commit();
                                return static::response('Your Task Is Successfully Created!', null, 200, 'success');
                            }
                        }

                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    return static::response('Error, Something went wrong, please try again!', $e, 500, 'error');
                }

            } else { #end has image attached.
                DB::beginTransaction();
                try {

                    if( $task->save() ) {

                        #task associate
                        if( $request->follower_option )
                            if( !(new FollowerTask)->saveFollowerTask($id, $task->id) )
                                array_push($error_message, 'Error while saving task follower option!');

                        if( $request->reputation )
                            if( !(new ReputationTask)->saveReputationTask($task->id, $request->reputation) )
                                array_push($error_message, 'Error while saving task reputation option!');

                        if( $request->activity_score )
                            if( !(new ActivityScoreTask)->saveActivityScoreTask($task->id, $request->activity_score) )
                                array_push($error_message, 'Error while saving task activity score option!');

                        if( $request->connection_option )
                            if( !(new ConnectionTask())->saveConnectionTask($task->id, $request->connection_option) )
                                array_push($error_message, 'Error while saving task connection option!' );

                        if( $request->task_completion_attachment_option )
                            if( !(new TaskOptionDetail)->saveTaskOptionDetail($task->id, $request->task_completion_attachment_option) )
                                array_push($error_message, 'Error while saving task attachment option');

                      

                        # START SAVING FREE TASK 
                        if($free_task_option && $free_task_limit > 0){
                            $free_task_mod = new UserFreeTask();
                            $free_task_mod->task_id = $task->id;   
                            $free_task_mod->user_id = $id;
                            $free_task_mod->role_id = $checkUser->role()->id;
                            $free_task_mod->completer_cnt = $free_task_applied;
                            $free_task_mod->task_reward = $this->free_task_reward;
                            $free_task_mod->task_fee = $free_task_fee;
                            $free_task_mod->total_budget = $free_total_budget;
                            if(!$free_task_mod->save()){
                                 array_push($error_message, 'Error while saving free task transaction!');
                            }
                        }
                        # END SAVING FREE TASK 

                          #task coming from wizard
                        if( $request->from_wizard )
                            if( ! (new TaskWizard)->saveTaskFromWizard($task->id, $id) ) {
                                DB::rollBack();
                                return static::failedTaskWizard($task->id);
                            }


                        if( count($error_message) > 0 ) {
                            DB::rollBack();
                            return static::response('', $error_message, 401, 'error');
                        } else {
                            #save slug
                            static::urlSlugGenerator($request->title, $task->id);

                            DB::commit();
                            return static::response('Your Task Is Successfully Created!', $task, 200, 'success');
                        }

                    }

                } catch(\Exception $e) {
                    DB::rollBack();
                    return static::response('Error, Something went wrong, please try again!', $e, 500, 'error');
                }


            }
        } else return static::response('Error, Not enough Superior Coin!', null, 401, 'error');

        return static::response('Kryptonia encounter server error, please reload the page!', null, 401, 'error');
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    /**
     * @param $request [task_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editTask($request) {
        // TODO: Implement editTask() method.

        $data = $this->editTaskStatus($request->slug);
        $datum = [];
        if( isset($data) ) {
            $datum['task'] = $data['task'];
            $datum['user'] = $data['user'];
            $datum['is_admin'] = $data['user']['type'] === 9 ? true : false;
            $datum['from_wizard'] = static::taskIfFromWizard($data['task']['id']);
            return static::response('Success', static::responseJwtEncoder( $datum, true), 200, 'success');
        }
        return static::response('Error, Unable to find task!', null, 401, 'error');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask($request) {
        // TODO: Implement updateTask() method.
        $errors_message = []; #error container

        $fee_charge = static::taskFeeCharge();
        $free_task_option = $request->has('free_task_option') ? $request->free_task_option : false;
        $user_limitations = static::userAccessLimitations();
        $free_task_limit = $user_limitations['free_task'];

        $user = User::find(Auth::id());
        $task = static::toBeUpdatedTask($request->task_id);
        
        if ($task->status_str === 'active') {
            return static::response( 'Error, Unable to update your running active task', null, 401, 'error' );            
        }

        $request->reputation_option = ($request->reputation_option == "") ? false : $request->reputation_option;
        $request->follower_option = ($request->follower_option == "") ? false : $request->follower_option;
        $request->connection_option = ($request->connection_option == "") ? false : $request->connection_option;
        

        $holdings = (new WalletRepository)->getholdings($user->id, true);

        #check if task is available.
        if(!$task)
            return static::response( 'Error, Unable to locate task!', 'null', 401, 'error' );

        #checked if task from wizard.
        // if( $user->type !== 9 )
        //     if( static::taskIfFromWizard($request->task_id) )
        //         array_push($errors_message, 'Kryptonia forbid your free first task to be updated!');

        #validation
        if( $request->total_point <= 0 )
            array_push($errors_message, 'Error, please enter total completer valid amount!');

        if( $request->reward <= 0 )
            array_push($errors_message, 'Error, please enter reward valid amount!');

        if( !$request->total_point )
            array_push($errors_message, 'Error, please enter total completer valid amount!');

        // if( $request->total_point < $task->total_point )
        //     array_push($errors_message, 'Error, total completer must not exceed below to task total completer!');

        if( !$request->reward )
            array_push($errors_message, 'Error, please enter reward valid amount!');

        // if( $request->reward <> $task->reward )
        //     array_push($errors_message,'Error, reward cannot be updated!');

        if( !$request->title )
            array_push($errors_message, 'Error, Title is required!');

        if( !$request->description )
            array_push($errors_message, 'Error, Description is required!');

        if( !$request->task_link )
            array_push($errors_message, 'Error, Task link is required!');

        if( !$request->category )
            array_push($errors_message, 'Error, Task category is required!');

        if( !static::urlValidator($request->task_link) )
            array_push($errors_message, 'Error, Task link is invalid!');

        
        if($request->reputation_option){
            if( $request->reputation ){
                if( !is_numeric( $request->reputation ) )
                    array_push($errors_message, 'Error, Reputation must be a number!');
            }else{
                array_push($errors_message, 'Error, Reputation is required!');
            }
               
            if( $request->activity_score ){
                if( !is_numeric( $request->activity_score ) )
                    array_push($errors_message, 'Error, Activity score must be a number');
            }else{
                array_push($errors_message, 'Error, Activity score is required!');
            }
        }else{
            $request->reputation = null;
            $request->activity_score = null;
        }

        if( count($errors_message) > 0 )
            return static::response('', $errors_message, 401, 'error');

        $coins = $request->reward * $request->total_point;
         # START FREE TASK CHECKING
        $remain_charge = 0;
        $free_task_applied = 0;
        $free_task_fee = 0;
        $free_total_budget = 0;
        $is_free_task = $task->is_free_task;
        $_free_charge = ($fee_charge <> 0) ? ($coins * $fee_charge) : 0;
        
        if($free_task_option && $free_task_limit > 0 && ($request->total_point != $task->total_point)){
            if($request->total_point > $task->total_point){
                $is_free_task = 1;
                $remain_completer = $request->total_point - $task->total_point; 
                if($remain_completer > $free_task_limit){
                    $free_task_applied = $free_task_limit;
                    $free_total_budget = $free_task_applied * $this->free_task_reward;
                    $free_task_fee = ($fee_charge <> 0) ? ($free_total_budget * $fee_charge) : 0;
                    $remain_charge = $remain_completer * $this->free_task_reward;
                    $_free_charge = ($fee_charge <> 0) ? ($remain_charge * $fee_charge) : 0;
                }else{
                    $remain_charge = 0;
                    $free_task_applied = $remain_completer;
                    $free_total_budget = $free_task_applied * $this->free_task_reward;
                    $free_task_fee = ($fee_charge <> 0) ? ($free_total_budget * $fee_charge) : 0;
                    $_free_charge = 0;
                }
                $fee_charge = ($fee_charge <> 0) ? ($coins * $fee_charge) : 0;
            }

        }else{
            $fee_charge = $_free_charge;
            $remain_charge = $coins;
        }
        # END FREE START CHECKING

        $coins_with_charge = $remain_charge + $_free_charge;
        #presist
        if( $holdings['available'] >= $coins_with_charge ) {
            $task->title = $request->title;
            $task->description = $request->description;
            $task->task_link = $request->task_link;
            $task->total_point = (int) $request->total_point;
            $task->reward = $request->reward;
            $task->total_rewards = 0;
            $task->final_cost = $coins;
            $task->category = $request->category;
            $task->fee_charge = $fee_charge;
            $task->is_free_task = $is_free_task;
            $task->status = 1;
            $task->expired_date = Carbon::now()->addDays(5)->toDateTimeString();

            if( $request->has('task_image') && $request->task_image <> null) {
                $file = $request->task_image;

                DB::beginTransaction();
                try {

                    if( $task->save() ) {

                         #reputation option
                        if( !is_null($request->reputation) OR is_null($request->reputation) )
                            if( !(new ReputationTask())->updateReputationTask($task->id, $request->reputation) )
                                array_push($errors_message, 'Error while updating task reputation score!');

                        #activity score option
                        if( !is_null($request->activity_score) OR is_null($request->activity_score) )
                            if( !(new ActivityScoreTask())->updateActivityScoreTask($task->id, $request->activity_score) )
                                array_push($errors_message, 'Error while updating task activity score!');
                       
                        #follower option
                        if( $request->follower_option OR !$request->follower_option )
                            if( !(new FollowerTask())->updateFollowerTask($task->id, $request->follower_option) )
                                array_push($errors_message, 'Error while updating task follower option!');

                        #task attachment option
                        if( $request->has_attachment OR !$request->has_attachment )
                            if( !(new TaskOptionDetail)->saveTaskOptionDetail( $task->id, $request->has_attachment ) )
                                array_push($errors_message, 'Error while saving task attachment option');

                        #connection option
                        if( $request->connection_option OR !$request->connection_option )
                            if( !(new ConnectionTask())->saveConnectionTask($task->id, $request->connection_option) )
                                array_push($errors_message, 'Error while saving task connection option');


                        if( !in_array($request->image_format, ['jpg', 'jpeg', 'png', 'gif']) )
                            array_push($errors_message, 'Task Image Is Not Supported!');

                         # START SAVING FREE TASK 
                        if($free_task_option && $free_task_limit > 0){
                            if($free_task_applied > 0){
                                $free_task_mod = new UserFreeTask();
                                $free_task_mod->task_id = $task->id;   
                                $free_task_mod->user_id = $user->id;
                                $free_task_mod->role_id = $user->role()->id;
                                $free_task_mod->completer_cnt = $free_task_applied;
                                $free_task_mod->task_reward = $this->free_task_reward;
                                $free_task_mod->task_fee = $free_task_fee;
                                $free_task_mod->total_budget = $free_total_budget;
                                if(!$free_task_mod->save()){
                                     array_push($errors_message, 'Error while saving free task transaction!');
                                }
                            }
                        }
                        # END SAVING FREE TASK 

                        if( count($errors_message) > 0 ) {
                            DB::rollBack();
                            return static::response('', $errors_message, 401, 'error');
                        } else {
                            #upload task image
                            $format = $request->image_format;
                            $task_id = $task->id;
                            if( static::uploadTaskImage($file, $task_id, $format, 'task',"") ) {
                                DB::commit();
                                return static::response('Your Task Is Successfully updated!', null, 200, 'success');
                            } else {
                                DB::rollBack();
                                return static::response('Error, Something went wrong, while uploading the task image!', null, 401, 'error');
                            }

                        }
                    }

                } catch(\Exception $e) {
                    DB::rollBack();
                    return static::response('Error, Something went wrong, please try again!', $task, 401, 'error');
                }

            } else { #end has image file
                DB::beginTransaction();
                try {
                    if( $task->save() ) {

                         #reputation option
                        if( !is_null($request->reputation) OR is_null($request->reputation) )
                            if( !(new ReputationTask())->updateReputationTask($task->id, $request->reputation) )
                                array_push($errors_message, 'Error while updating task reputation score!');

                        #activity score option
                        if( !is_null($request->activity_score) OR is_null($request->activity_score) )
                            if( !(new ActivityScoreTask())->updateActivityScoreTask($task->id, $request->activity_score) )
                                array_push($errors_message, 'Error while updating task activity score!');
                              
                        #follower option
                        if( $request->follower_option OR !$request->follower_option )
                            if( !(new FollowerTask())->updateFollowerTask($task->id, $request->follower_option) )
                                array_push($errors_message, 'Error while updating task follower option!');

                        #task attachment option
                        if( $request->has_attachment OR !$request->has_attachment )
                            if( !(new TaskOptionDetail)->saveTaskOptionDetail( $task->id, $request->has_attachment ) )
                                array_push($errors_message, 'Error while saving task attachment option');

                        #connection option
                        if( $request->connection_option OR !$request->connection_option )
                            if( !(new ConnectionTask())->saveConnectionTask($task->id, $request->connection_option) )
                                array_push($errors_message,'Error while saving task connection option');


                         # START SAVING FREE TASK 
                        if($free_task_option && $free_task_limit > 0){
                            if($free_task_applied > 0){
                                $free_task_mod = new UserFreeTask();
                                $free_task_mod->task_id = $task->id;   
                                $free_task_mod->user_id = $user->id;
                                $free_task_mod->role_id = $user->role()->id;
                                $free_task_mod->completer_cnt = $free_task_applied;
                                $free_task_mod->task_reward = $this->free_task_reward;
                                $free_task_mod->task_fee = $free_task_fee;
                                $free_task_mod->total_budget = $free_total_budget;
                                if(!$free_task_mod->save()){
                                     array_push($errors_message, 'Error while saving free task transaction!');
                                }
                            }
                        }
                        # END SAVING FREE TASK 

                        if( count($errors_message) > 0 ) {
                            DB::rollBack();
                            return static::response('', $errors_message, 401, 'error');
                        } else {
                            DB::commit();
                            return static::response('Success, Task successfully updated', $task, 200, 'success');
                        }
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    return static::response('Error, Something went wrong, please try again', $e, 401, 'error');
                }
            }
        } else { # end enough coins
            return static::response('Error, Not enough Superior Coin!', null, 401, 'error');
        }
        return static::response('Kryptonia encounter server error, please reload the page!', null, 500, 'error');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hideTask($request) {
        // TODO: Implement hideTask() method.
        $error_messages = [];
        $data = ['task_id' => $request->task_id, 'user_id' => Auth::id()];

        DB::beginTransaction();

        try {
            $task = Task::where('id', $data['task_id'])->first();

            if( !$task )
                $error_messages[] = 'Unable to find task!';

            if( $task ) {
                $hidden = TaskHidden::where(function($query) use (& $data) {
                    $query->where('user_id', $data['user_id'])
                        ->where('task_id', $data['task_id'])
                        ->where('hidden',1);
                })->first();

                if( $hidden )
                    $error_messages[] = 'You already hide this task!';

                if( count($error_messages) > 0 ) {
                    DB::rollBack();
                    return static::response('Error', $error_messages, 401, 'error');
                }


                if( !$hidden ) {
                    if( (new TaskHidden)->saveData($data) ) {
                        $task->hidden = (bool) 1;
                        if( $task->save() ) {
                            DB::commit();
                            return static::response('Task successfully set to hidden', null, 200, 'success');
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $error_messages[] = 'Ops! Something went wrong, please try again';
            DB::rollBack();
            return static::response('Error', $error_messages, 500, 'error');
        }
    }

    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unHideTask($request) {
        // TODO: Implement unHideTask() method.
        $user_id = Auth::id();
        $task = $this->unHideTaskStatus($request->task_id, $user_id);
        if($task['bol'] == true){
            return static::response('Task successfully un-hide', null, 200, 'success');
        }else{
            return static::response($task['error_msg'], null, 401, 'error');
        }   
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTask($request) {
        // TODO: Implement deleteTask() method.
        $user_id = Auth::id();
        $task = $this->deleteTaskStatus($request->task_id, $user_id);
        if($task)
            return static::response('Task successfully set to archived', null, 200, 'success');

        if($task === 2)
            return static::response('Kryptonia forbid your free first task to be deleted!', null, 400, 'error');

        return static::response('Kryptonia encounter server error, please reload the page', null, 401, 'error');
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateTask($request) {
        // TODO: Implement activateTask() method.
        $error_messages = [];

        $task_fee_charge = static::taskFeeCharge();

        $task = $this->activateTaskStatus($request->task_id);
        $user_id = Auth::id();

        DB::beginTransaction();
        try {
            $holdings = (new WalletRepository())->getHoldings($user_id, true);
            $sup_available = $holdings['available'];
            if( !$task )
                $error_messages[] = 'Unable to locate the task!';

            if( $task->expired_date < Carbon::now() ) {
                #full reset
                $coins = $task->reward * $task->total_point;
                $fee_charge = ($task_fee_charge <> 0) ? ($coins * $task_fee_charge) : 0;
                $coins_with_charge = $coins + $fee_charge;
                if($sup_available < $coins_with_charge)
                    $error_messages[] = 'You Have Insufficient SUP!';

                if( count($error_messages) > 0 ) {
                    DB::rollBack();
                    return static::response('Error', $error_messages, 401, 'error');
                }

                $task->expired_date = Carbon::now()->addDays(5);
                $task->final_cost = $coins;
                $task->total_rewards = 0;
                $task->status = 1;
                $task->fee_charge = $fee_charge;

                if($task->save()) {
                    DB::commit();
                    return static::response('Task successfully updated!', null, 200, 'success');
                } else {
                    DB::rollBack();
                    return static::response('Error, while updating the task!, please reload the page!', null, 401, 'error');
                }
            } else {
                if($task->final_cost === 0) {
                    #reset the completer and status
                    $coins = $task->reward * $task->total_point;
                    $fee_charge = ($task_fee_charge <> 0) ? ($coins * $task_fee_charge) : 0;
                    $coins_with_charge = $coins + $fee_charge;
                    if($sup_available < $coins_with_charge)
                        $error_messages[] = 'You Have Insufficient SUP!';

                    if( count($error_messages) > 0 ) {
                        DB::rollBack();
                        return static::response('Error', $error_messages, 401, 'error');
                    }

                    $task->final_cost = $coins;
                    $task->total_rewards = 0;
                    $task->status = 1;
                    $task->fee_charge = $fee_charge;

                    if($task->save()) {
                        DB::commit();
                        return static::response('Task successfully updated!', null, 200, 'success');
                    } else {
                        DB::rollBack();
                        return static::response('Error, while updating the task!, please reload the page!', null, 401, 'error');
                    }
                } else {
                    $coins = $task->reward * $task->total_point;
                    $fee_charge = ($task_fee_charge <> 0) ? ($coins * $task_fee_charge) : 0;
                    $coins_with_charge = $coins + $fee_charge;

                    if($sup_available < $coins_with_charge)
                        $error_messages[] = 'You Have Insufficient SUP!';

                    if( count($error_messages) > 0 ) {
                        DB::rollBack();
                        return static::response('Error', $error_messages, 401, 'error');
                    }

                    #reset the status
                    $task->fee_charge = $fee_charge;
                    $task->status = 1;
                    if($task->save()) {
                        DB::commit();
                        return static::response('Task successfully updated!', null, 200, 'success');
                    } else {
                       DB::rollBack();
                        return static::response('Error, while updating the task!, please reload the page!', null, 401, 'error');
                    }
                }
            }
        } catch (\Exception $e) {
            $error_messages[] = 'Ops! Something Went Wrong, Please Try Again!';
            DB::rollBack();
            return static::response('Error', $error_messages, 500, 'error');
        }
    }


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deActivateTask($request) {
        // TODO: Implement deActivateTask() method.
        $task = $this->deActivateTaskStatus($request->task_id);
        if($task) {
            // $task->expired_date = Carbon::now()->subDay();
            $task->status = 0;
            if($task->save())
                return static::response('This task successfully deactivated', null, 200, 'success');
        }
        return static::response('Error!, Something went wrong please reload the page', null, 401, 'error');
    }

    public function getImageMetaTag($request){
            libxml_use_internal_errors(true);
            $c = file_get_contents($request->url);
            $d = new \DomDocument();
            $d->loadHTML($c);
            $xp = new \domxpath($d);
            $image = "";
            foreach ($xp->query("//meta[@property='og:image']") as $el) {
                $image = $el->getAttribute("content");
            }
            if($image){
                return static::response('Image metatag found', $image, 200, 'success');
            }
            else{
                return static::response('No image metatag found', null, 401, 'error');
            }
            return static::response('Error!, Something went wrong please reload the page', null, 401, 'error');
            
            // foreach ($xp->query("//meta[@property='og:description']") as $el) {
            //     echo $el->getAttribute("content");
            // }
    }

    public function showTask($request) {
        // TODO: Implement showTask() method.
        $data = static::showTaskAssociate($request->slug);
        if( $data )
            return static::response('Success', static::responseJwtEncoder($data), 200, 'success');
        return static::response('Kryptonia encounter server error, please reload the page!', null, 401, 'error');
    }

    public function completeTask($request) {
        // TODO: Implement completeTask() method.

        $error_container = [];

        DB::beginTransaction();
        try {
            $user = User::find(Auth::id());
            $task = static::activeTaskFindByIdOrSlug($request->task_id, 'id');
          
            if($task->user_id === $user->id)
                array_push($error_container, ["own_task" => "Error, You can't complete your own created task!"]);
          
            if($task) {
                $available = (new WalletRepository())->getHoldings($task->user_id, true, true);
              
                if($user->ban > 0){
                    array_push($error_container, ['user_status' => 'Error, account is banned!']);
                }

                if($user->status == 0){
                    array_push($error_container, ['user_status' => 'Error, please activate your account first!']);
                }
        
                if($user->verified == 0){
                    array_push($error_container, ['user_status' => 'Error, please connect atleast one of your social media first!']);
                }
        
                #checked if user in revoke list
                if( static::checkIfUserInRevokeList ($user->id, $task->id) )
                    array_push($error_container, ['revoke' => 'Error, you are currently revoked from this task!']);

                #checked if user in block list
                if( static::checkIfUserInBlockList($user->id, $task->user_id) )
                    array_push($error_container, ['block' => 'Error, you are currently blocked from this task creator!']);

                #check if task already completed by current user
                if( static::taskIfCompleted($task->id, $user->id) )
                    array_push($error_container, ['completed' => 'Error, you already completed this task!']);

                #checked if task is currently hidden
                if( static::taskCheckIfHidden($task->id, $user->id) )
                    array_push($error_container, ['hidden' => 'Error, task is currently hidden!']);

                #checked if task is only for follower
                if( !static::checkTaskFollowerRequired($task->id, $task->user_id, $user->id) )
                    array_push($error_container, ['follower' => 'Error, Only the follower of the task creator can complete this task!']);

                #checked if task required reputation
                if( !static::checkTaskReputationRequired($task->id, $user->id) )
                    array_push($error_container, ['reputation' => 'Error, your reputation score does not meet the task requirements!']);

                #checked if task required activity
                if( !static::checkTaskActivityRequired($task->id, $user->id) )
                    array_push($error_container, ['activity' => 'Error, your activity score does not meet the task requirements!']);

                #checked if task required connection.
                if( !static::checkTaskConnectionRequired($task, $user->id) )
                    array_push($error_container, ['connection' => 'Error, available only for task creator connection!']);

                #checked SUP task creator available
                // return static::response('TEST!', $available, 200, 'error');
                if( $available['hold'] < $task->reward )
                    array_push($error_container, 'Error, The owner of the task is out of Superior Coin (SUP)!');

                #checked for completer available
                if( $task->final_cost <= 0 )
                    array_push($error_container, ['completer' => 'Error, Task already completed by other user!']);

                if( count($error_container) > 0 ) {
                    DB::rollBack();
                    return static::response('Error in completing task', $error_container, 401, 'error');
                }
                $filename  ="";
                if( (new TaskUser())->saveData([
                    'user_id' => $user->id, 'task_id' => $task->id,
                    'reward' => $task->reward, 'task_creator' => $task->user_id
                ]) )
                    {
                        if( $request->has_attachment ) {
                            if( !in_array($request->format, ['jpg', 'jpeg', 'png', 'gif']) ) {
                                array_push($error_message, ['img_type' => 'Task Image Is Not Supported!']);
                            }
                          
                            $filename = $request->filename;
                            if( !static::uploadTaskImage($request->attachment, $task->id, $request->format, 'attachment', $filename) ) {
                                array_push($error_container, ['attachment' => 'Error, Attachment not uploaded!']);
                            }else{
                                event(new NewTaskTransaction($user, $task, 'attachment'));
                            }
                        }

                        if( count($error_container) > 0 ) {
                            DB::rollBack();
                            return static::response('Error in completing task', $error_container, 401, 'error');
                        }

                        #prepare data for transaction history
                        $history = 'This task was completed by ' . $user->name;
                        if( (new TaskTransactionHistory())->saveData([
                            'task_id' => $task->id, 'user_id' => $user->id,
                            'type' => 'completion', 'history' => $history
                        ]) )
                        {
                            $task->total_rewards += 1;
                            $task->final_cost = ($task->reward * $task->total_point) - ($task->reward * $task->total_rewards);
                            if( $task->save() ) {
                                DB::commit();
                                event(new NewTaskTransaction($user, $task, 'completed'));
                                return static::response('Task is successfully completed!', $task, 200, 'success');
                            }
                        }
                    }
            }
        } catch(\Exception $e) {
            DB::rollBack();
            return static::response('Error, Something went wrong while completing the task!', $e, 401, 'error');
        }
    }

    public function taskDetails($request){

        $tasks = $this->listOfTaskDetails($request);

        if(count($tasks) > 0)
            return static::response(null, static::responseJwtEncoder($tasks), 201, 'success');
            return static::response('No Data Fetched', null, 200, 'success');

    }

     /**
     * @param $request ['search_key','offset','category_filter']
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchTaskList($request) {
        $offset = $request->has('offset') ? $request->offset : 0;
        $this->query = $request->has('search_key') ? $request->search_key : '';
        $this->category = $request->has('category_filter') ? $request->category_filter : '';

        $completerTaskId = Auth::user()->completedTask->pluck('id');
        $revokedTaskId = Auth::user()->revokedUser->pluck('id');
        $blockTaskId = Auth::user()->blockTaskUser->pluck('id');
        $hidden = Auth::user()->hiddenTasks->pluck('task_id');

        $tasks = Task::leftjoin('users as u', 'u.id', '=', 'tasks.user_id')
                    ->select(['u.name','u.username','u.id','tasks.*'])
                    ->where('tasks.user_id', '<>', Auth::id())
                    ->where('tasks.status', 1)
                    ->where('tasks.expired_date', '>=', Carbon::now())
                    ->where('tasks.final_cost', '<>', 0)
                    ->whereNotIn('tasks.id', $hidden)
                    ->whereNotIn('tasks.id', $completerTaskId)
                    ->whereNotIn('tasks.id', $revokedTaskId)
                    ->whereNotIn('tasks.user_id', $blockTaskId)
                    ->where(function($q){
                        if($this->category <> ''){
                            $q->where('tasks.category','=',$this->category)
                               ->where(function($qs){
                                    $qs->orWhere('title', 'LIKE', '%'.$this->query.'%')
                                       ->orWhere('u.name', 'LIKE', '%'.$this->query.'%');
                               });
                        }else{
                            $q->orWhere('title', 'LIKE', '%'.$this->query.'%')
                              ->orWhere('u.name', 'LIKE', '%'.$this->query.'%');
                        }
                     })
                    ->orderBy('tasks.reward', 'desc')
                    ->orderBy(DB::raw('RAND()'))
                    ->groupBy('tasks.user_id')
                    ->offset($offset)
                    ->limit(25)
                    ->get();

            if(count($tasks) > 0)
            return static::response('Successfully Load specific Tasks',static::responseJwtEncoder($tasks), 200, 'success');
        return static::response('No Data Fetched', null, 401, 'error');

    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateTaskUrl($request) {
        // TODO: Implement generateTaskUrl() method.
        $task_id = $request->has('task_id') ? $request->task_id : null;

        if( !is_null($task_id) ) {
            $slug = static::generateTaskURLHelper($task_id);
            if( $slug )
                return static::response('Task URL successfully set', $slug, 200, 'success');
            return static::response('Error Unable to generate task URL', null, 401, 'error');
        }
        return static::response('Error Unable to generate task URL', null, 401, 'error');
    }

     /**
     * @param $request [task_id, offset, search_key]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskCompleterList($request) {
        $task_slug = $request->slug;
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : 20;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $data = [];

        $task = Task::where(function($query) use ($task_slug) {
            $query->where('slug', $task_slug);
        })->first();
        
        $task_id = $task->id;

        $completer_query = TaskUser::select(['task_user.*','u.name', 'u.username', 'u.email', 't.title'])
                                    ->leftJoin('users as u','u.id','=','task_user.user_id')
                                    ->leftJoin('tasks as t','t.id', 'task_user.task_id')
                                    ->where('task_user.task_id', $task_id)
                                    ->where('task_user.revoke', (bool) 0);
                                
        if($search_key <> ''){
            $completer_query = $completer_query->where('u.name', 'LIKE', '%' . $search_key . '%');
        }

        $completer = $completer_query->offset($offset)->limit($limit)->orderBy('task_user.created_at', 'desc')->get();
        
        $completer->each(function($item, $key) use (& $data) {
            if($item->attachment->count() > 0) {
                $item->attachment->each(function($a_item) use ($item, $key, & $data) {
                  
                    if( $item->user <> null ) {
                        $data[$key]['name'] = $item->name;
                        $data[$key]['username'] = $item->username;
                        $data[$key]['user_id'] = $item->user_id;
                        $data[$key]['task_completion_date'] = $item->created_at;
                        $data[$key]['task_id'] = $item->task_id;
                        $data[$key]['has_attachment'] = true;
                        // $data[$key]['attachment'] = $a_item->attachment_file;
                        $data[$key]['attachment'] = $this->getAttachment($item->attachment, $item->user_id);
                        $data[$key]['title'] = $item->title;
                        $data[$key]['is_blocked'] = static::checkIfUserInBlockList($item->user_id,$item->task_creator);
                        
                    }
                });
            } else {
                if( isset($item->user) ) {
                    $data[$key]['name'] = $item->user->name;
                    $data[$key]['username'] = $item->user->username;
                    $data[$key]['user_id'] = $item->user->id;
                    $data[$key]['task_completion_date'] = $item->created_at;
                    $data[$key]['task_id'] = $item->task_id;
                    $data[$key]['has_attachment'] = false;
                    $data[$key]['attachment'] = null || '';
                    $data[$key]['title'] = $item->title;
                    $data[$key]['is_blocked'] = static::checkIfUserInBlockList($item->user_id,$item->task_creator);
                }
            }
        });
    
        if( count($data) > 0 )
            return static::response('Data Fetched', static::responseJwtEncoder(array_values($data)), 200, 'success');
        return static::response('No Data Fetched', null, 401, 'error');
    }
    private function getAttachment($data, $user_id){
        $r = $data->toArray();
       if($data){
            for($x=0;$x<count($r);$x++){
                if($r[$x]['user_id'] == $user_id){
                    return $r[$x]['attachment_file'];
                }
            }
       }
       return null;
    }

     /**
     * @param $request [task_id, offset, search_key]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskRevokeCompleterList($request) {
        $task_id = $request->task_id;
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : 20;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $this->query = $search_key;
        $data = [];
        $completers = TaskUser::select(['task_user.*','u.name AS completer','u.username AS completer_username','b.user_id as task_user_id','task_user.updated_at as revoked_dt'])
                                ->leftjoin('tasks as b', 'b.id', '=', 'task_user.task_id')
                                ->leftJoin('users as u','u.id','=','task_user.user_id')
                                ->where('task_user.task_id', $task_id)->where('revoke', 1)
                                ->where(function($q){
                                    if($this->query <> ''){
                                        $q->whereDate('task_user.created_at','=',$this->query)
                                          ->orWhere('u.name', 'LIKE', '%'.$this->query.'%');
                                    }
                                })
                                ->offset($offset)
                                ->limit($limit)
                                ->orderByDesc('task_user.created_at')->get();


        if(count($completers) > 0){
            foreach($completers as $key => $value){
                $status = static::getRevokeRewardType($value->task_id, $value->task_user_id, $value->user_id);
                $item = [
                    'completer_id' => $value->user_id,
                    'completer' => $value->completer,
                    'completer_username' => $value->completer_username,
                    'revoked_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->revoked_dt)->toDateTimeString(),
                    'type' => ($status == "") ? "Revoke" : $status
                ];
                array_push($data,$item);
            }

            return static::response('',static::responseJwtEncoder($data), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }


    /**
     * @param $request [task_id, limit]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskComments($request) {
        $task_id = $request->task_id;
        $limit = $request->has('limit') ? $request->limit : 10;
        $comment_list = [];
        $comments = KryptoniaTaskComment::with('taskCommentDetail')
                                        ->where('task_id', $task_id)
                                        ->active()->orderByDesc('created_at')->get();

        if(count($comments) > 0){
            foreach($comments as $key => $comment){
                $item = [
                    'comment_id' => $comment->id,
                    'timeago' => $comment->created_at->diffForHumans(),
                    'user_details'  => User::find($comment->taskCommentDetail[0]->user_id),
                    'comment_user_id' => $comment->taskCommentDetail[0]->user_id,
                    'comment' => $comment->comment,
                    'comment_date' => Carbon::createFromFormat('Y-m-d H:i:s',$comment->created_at)->toDateTimeString(),
                    'count_replies' => static::countTaskActiveReplies($comment->id),
                    'reply' => [],
                    'collapse' => false
                ];

                array_push($comment_list,$item);

                $replies = KryptoniaTaskSubComment::with('taskSubCommentDetail')
                                                  ->where('parent_comment_id', $comment->id)
                                                  ->active()->orderByDesc('created_at')->get();

                $reply_list = [];
                if(count($replies) > 0){
                    foreach($replies as $reply){
                        $item = [
                            'reply_id' => $reply->id,
                            'timeago' => $reply->created_at->diffForHumans(),
                            'user_details'  => User::find($reply->taskSubCommentDetail[0]->user_id),
                            'parent_comment_id' => $reply->parent_comment_id,
                            'comment' => $reply->comment,
                            'reply_user_id' => $reply->taskSubCommentDetail[0]->user_id,
                            'reply_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$reply->created_at)->toDateTimeString()
                        ];

                        array_push($reply_list,$item);
                    }
                }
                if(count($reply_list) > 0){
                    $reply_list = collect($reply_list)->forPage(0,$limit);
                    $comment_list[$key]['reply'] = $reply_list;
                }
            }
            $comment_list = collect($comment_list)->forPage(0,$limit);

            return static::response('',static::responseJwtEncoder($comment_list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    /**
     * @param $request [task_id, comment_id, limit]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function specificTaskComment($request) {
        $task_id = $request->task_id;
        $comment_id = $request->comment_id;
        $limit = $request->has('limit') ? $request->limit : 10;
        $comment_list = [];
        $comments = KryptoniaTaskComment::with('taskCommentDetail')
                                        ->where('task_id', $task_id)
                                        ->where('id', $comment_id)
                                        ->active()->get();

        $attachment_dir = 'public/image/uploads/tasks/task-comment-attachment/';

        if(count($comments) > 0){
            foreach($comments as $key => $comment){
                $item = [
                    'comment_id' => $comment->id,
                    'timeago' => $comment->created_at->diffForHumans(),
                    'user_details'  => User::find($comment->taskCommentDetail[0]->user_id),
                    'comment_user_id' => $comment->taskCommentDetail[0]->user_id,
                    'comment' => $comment->comment,
                    'comment_date' => Carbon::createFromFormat('Y-m-d H:i:s',$comment->created_at)->toDateTimeString(),
                    'comment_attachment' => ($comment->image <>'') ? $attachment_dir.$comment->image : "",
                    'reply' => []
                ];

                array_push($comment_list,$item);

                $replies = KryptoniaTaskSubComment::with('taskSubCommentDetail')
                                                  ->where('parent_comment_id', $comment->id)
                                                  ->active()->get();

                $reply_list = [];
                if(count($replies) > 0){
                    foreach($replies as $reply){
                        $item = [
                            'reply_id' => $reply->id,
                            'timeago' => $reply->created_at->diffForHumans(),
                            'user_details'  => User::find($reply->taskSubCommentDetail[0]->user_id),
                            'parent_comment_id' => $reply->parent_comment_id,
                            'comment' => $reply->comment,
                            'reply_user_id' => $reply->taskSubCommentDetail[0]->user_id,
                            'reply_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$reply->created_at)->toDateTimeString(),
                            'reply_attachment' => ($reply->image <>'') ? $attachment_dir.$reply->image : ""
                        ];

                        array_push($reply_list,$item);
                    }
                }
                if(count($reply_list) > 0){
                    $comment_list[$key]['reply'] = $reply_list;
                }
            }
            $comment_list = collect($comment_list)->forPage(0,$limit);

            return static::response('',static::responseJwtEncoder($comment_list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }


    /**
     * @param $request [task_comment_img]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskCommentUploadImage($request) {
        $comment_id = $request->comment_id;
        $type = $request->type;
        $task_comment_img = $request->task_comment_img;
        $datenow = date('YmdHis');
        if($request->hasFile('task_comment_img')){
            $dir = base_path('public/image/uploads/tasks/task-comment-attachment/');
            if(false === File::exists($dir)){
                File::makeDirectory($dir, 0755, true);
            }
            $filename = 'comment_task_image_' . $datenow . '.'. $task_comment_img->extension();
            
            $uploadedFile = Image::make($task_comment_img)->save($dir . $filename);

            switch ($type) {
                case 'comment':
                    $task_comment = KryptoniaTaskComment::find($comment_id);
                    if($task_comment) {
                        $task_comment->image = $filename;
                        $task_comment->save();
                    }
                break;
    
                case 'reply-comment':
                    $task_sub_comment = KryptoniaTaskSubComment::find($comment_id);
                    if($task_sub_comment) {
                        $task_sub_comment->image = $filename;
                        $task_sub_comment->save();
                    }
                break;
    
                default:
                    return static::response('Please specify task comment type!', null, 201, 'success');
                break;
            }
        }

        if($uploadedFile){
            return static::response('Successfully uploaded image in comment!',$filename, 200, 'success');
        }
        return static::response('Failed to upload image in comment', null, 400, 'error');
        
    }

    /**
     * @param $request [task_id, comment, user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTaskComment($request) {
        $user_id = Auth::id();
        $task_id = $request->task_id;
        $comment = $request->comment;

        $completer = User::find($user_id);
        $task = Task::find($task_id);

        if($task_id <> ''){
            $task_comment = new KryptoniaTaskComment;
            $task_comment->task_id = $task_id;
            $task_comment->comment = str_replace('@', '', $comment);
            if($task_comment->save()) {
                $task_comment_detail = new KryptoniaTaskCommentDetail;
                $task_comment_detail->comment_id = $task_comment->id;
                $task_comment_detail->user_id = $user_id;
                if($task_comment_detail->save()) {
                    event(new NewTaskTransaction($completer, $task, 'comment'));
                    return static::response('Comment Successfully Posted!',null, 200, 'success');
                }
            }
            return static::response('Failed to post the comment!', null, 201, 'success');
        }
        return static::response('Please specify the Task Id!', null, 201, 'success');
    }

    /**
     * @param $request [comment_id, comment, user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTaskSubComment($request) {
        $user_id = Auth::id();
        $comment_id = $request->comment_id;
        $comment = $request->comment;

        $comment_info = KryptoniaTaskComment::where('id',$comment_id)->first();
        $completer = User::find($user_id);
        $task = Task::find($comment_info->task_id);

        if($comment_id <> ''){
            $task_sub_comment = new KryptoniaTaskSubComment;
                $task_sub_comment->comment = $comment;
                $task_sub_comment->parent_comment_id = $comment_id;
                if($task_sub_comment->save()) {
                    $task_sub_comment_detail = new KryptoniaTaskSubCommentDetail;
                    $task_sub_comment_detail->sub_comment_id = $task_sub_comment->id;
                    $task_sub_comment_detail->user_id = $user_id;
                    if($task_sub_comment_detail->save()){
                        event(new NewTaskTransaction($completer, $task, 'comment'));
                        return static::response('Comment Successfully Posted!',null, 200, 'success');
                    }
                }
            return static::response('Failed to post the comment!', null, 201, 'success');
        }
        return static::response('Please specify the Comment Id!', null, 201, 'success');
    }

    /**
     * @param $request [type, comment_id, comment]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTaskComment($request) {
        $type = $request->type;
        $comment_id = $request->comment_id;
        $comment = $request->comment;
        switch ($type) {
            case 'comment':
                $task_comment = KryptoniaTaskComment::find($comment_id);
                if($task_comment) {
                    $task_comment->comment = $comment;
                    if($task_comment->save()){
                        return static::response('Comment Successfully Updated!',null, 200, 'success');
                    }
                }
                return static::response('Failed to update the comment!', null, 201, 'success');
            break;

            case 'reply-comment':
                $task_sub_comment = KryptoniaTaskSubComment::find($comment_id);
                if($task_sub_comment) {
                    $task_sub_comment->comment = $comment;
                    if($task_sub_comment->save()){
                        return static::response('Comment Successfully Updated!',null, 200, 'success');
                    }
                }
                return static::response('Failed to update the comment!', null, 201, 'success');
            break;

            default:
                return static::response('Please specify task comment type!', null, 201, 'success');
            break;
        }
    }

     /**
     * @param $request [task_comment_id, type]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTaskComment($request) {
        $task_comment_id = $request->task_comment_id;
        $type = $request->type;

        switch ($type) {
            case 'comment':
                $comment_task = KryptoniaTaskComment::find($task_comment_id);
                if($comment_task){
                    $comment_task->status = (bool) 0;
                    if($comment_task->save()) {
                        // event( new NewDeleteTaskCommentDetailEvent($comment_task->id)); // TODO: implement NewDeleteTaskCommentDetailEvent() event
                        return static::response('Comment Successfully Removed!',null, 200, 'success');
                    }
                }
                return static::response('Failed to remove the comment!', null, 201, 'success');
            break;

            case 'reply-comment':
                $sub_comment_task = KryptoniaTaskSubComment::find($task_comment_id);
                if($sub_comment_task) {
                    $sub_comment_task->status = (bool) 0;
                    if($sub_comment_task->save()) {
                        // event( new NewDeleteSubTaskCommentDetailEvent($sub_comment_task->id)); // TODO: implement NewDeleteSubTaskCommentDetailEvent() event
                        return static::response('Comment Successfully Removed!',null, 200, 'success');
                    }
                }
                return static::response('Failed to remove the comment!', null, 201, 'success');
            break;

            default:
                return static::response('Please specify task comment type!', null, 201, 'success');
            break;
        }
    }

    /**
     * @param $request [task_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countTaskComments($request) {
        $task_id = $request->task_id;
        $data = [];
        $countcomment = static::countTaskActiveComments($task_id);

        $task_info = Task::find($task_id);
        $count_completers = static::countTaskCompleter($task_info->slug);

        $data['comments'] = $countcomment;
        $data['completers'] = $count_completers;

        return static::response('',$data, 200, 'success');
    }

    public function revokeUserFromTask($request) {
        // TODO: Implement revokeUserFromTask() method.
        $user_id = Auth::id();
        $completer_user_id = $request->completer_user_id;
        $task_id = $request->task_id;
        $reason = $request->reason;

        $completer = User::find($completer_user_id);
        $task = Task::find($task_id);

        if( static::_revokeUser($user_id, $completer_user_id, $task_id, $reason) ){
            event(new NewTaskTransaction($completer, $task, 'revoked'));
            return static::response('Successfully Revoked From Task', null, 200, 'success');
        }
        return static::response('Unable To Revoked From Task', null, 401, 'error');
    }

    public function blockUserFromTask($request) {
        // TODO: Implement blockUserFromTask() method.
        $user_id = Auth::id();
        $completer_user_id = $request->completer_user_id;
        $task_id = $request->task_id;

        $completer = User::find($completer_user_id);
        $task = Task::find($task_id);

        if( static::_blockUserFromTask(['user_id' => $user_id, 'completer_id' => $completer_user_id, 'task_id' => $task_id]) ){
            event(new NewTaskTransaction($completer, $task, 'blocked'));
            return static::response('Successfully Blocked And Revoked From All Your Task', null, 200, 'success');
        }
        return static::response('Unable To Block And Revoke From Your Task', null, 401, 'error');
    }

    public function taskHistory($request) {
       $limit = $request->has('limit') ? $request->limit : 10;
       $search_key = $request->has('search_key') ? $request->search_key : "";
       $category = $request->has('category') ? $request->category : "";

       $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $task_query = Task::query();

        if($search_key <> ''){
            $task_query = $task_query->where('title', 'LIKE', '%'.$search_key.'%');
        }

        if($category <> ''){
            $task_query = $task_query->where('category', 'LIKE', '%'.$category.'%');
        }

        if($filter_date <> ''){
            $task_query = $task_query->whereDate('created_at', '=', $filter_date);
        }

        $task = $task_query->orderByDesc('created_at')->limit($limit)->get();

       $task_count = Task::count();
       $data = [];
       $list = [];

       if(count($task) > 0){
            foreach($task as $row => $value){
                $item = [
                    'task_id' => $value->id,
                    'slug' => $value->slug,
                    'task_title' => $value->title,
                    'description' => $value->description,
                    'link' => $value->task_link,
                    'reward' => $value->reward . " SUP",
                    'total_completer' => $this->countTaskCompleter($value->slug),
                    'total_revoke_user' => $this->countTaskUserRevoke($value->id),
                    'status' => $this->getStatus($value->id),
                    'category' => $value->category,
                    'created_at' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    
                ];

                $data[] = $item;
            }

            $list['list'] = $data;
            $list['total_count'] = $task_count;

            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
       }
       return static::response('No Data Fetched!', null, 400, 'failed');
    }

    public function viewTaskAttachment($request){
        $task_id = $request->task_id;
        $user_id = $request->user_id;

        $attachment = TaskCompletionDetail::select(['attachment_file'])->where('task_id', $task_id)->where('user_id', $user_id)->first();

        if($attachment){
            return static::response(null,$attachment, 200, 'success');
        }
        return static::response('No Data Fetched!', null, 400, 'failed');
    }

    public function getFeaturedTaskCreator($request){
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : 21;
        $archived_tasks = TaskDeleted::where('status', 1)->pluck('task_id');
        $data = [];
        $list_query = Task::with('user')->whereHas('user', function ($user) {
            $user->with('withRole')->where(function($q) {
                $q->whereHas('withRole', function ($role) {
                    $role->with('limitations')->whereHas('limitations', function ($limitations) {
                        $limitations->where('slug', 'task-featured-creator')->where('value', 1);
                    });
                })->orWhere('type', 9);
            })->where('ban', 0)->where('status', 1)->where('verified', 1)->where('agreed', 1);
        })->groupBy('user_id')->selectRaw('*, SUM(reward * total_point) AS total_rewards_spent, COUNT(id) AS total_tasks_post');
        $count = $list_query->count();
        $list = $list_query->orderBy('total_rewards_spent', 'DESC')->skip($offset)->take($limit)->get();
        $list = $list->map(function ($task) {
            return [
                'user_id' => $task->user_id,
                'name' => $task->user->name,
                'email' => $task->user->email,
                'username' => $task->user->username,
                'total_tasks_post' => $task->total_tasks_post,
                'total_rewards_spent' => $task->total_rewards_spent,
            ];
        });
        
        $data['list'] = $list;
        $data['count'] = $count;

        if(count($list) > 0){
            return static::response(null, static::responseJwtEncoder($data), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'failed');
    }

    public function taskBlockedUsers($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $list = [];
        $blocked_query = BlockUserTask::select(['block_user_tasks.task_user_id as blocked_id', 'u.name', 
                                        'u.username', 'u.email', 'block_user_tasks.created_at as blocked_date'])
                                        ->leftJoin('users as u','u.id','=','block_user_tasks.task_user_id')
                                        ->where('block_user_tasks.user_id',$user_id)
                                        ->where('block_user_tasks.block',1)
                                        ->groupBy('block_user_tasks.task_user_id');
        
        if($filter_date <> ''){
            $blocked_query = $blocked_query->whereDate('block_user_tasks.created_at',$filter_date);
        }

        $count = $blocked_query->count();
        $blocked_users = $blocked_query->orderByDesc('block_user_tasks.created_at')->offset($offset)->limit($limit)->get()->toArray();

        $list['count'] = $count;
        $list['list'] = $blocked_users;

        if(count($blocked_users) > 0){
            return static::response(null, static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'failed');
    }

    public function countActiveTask(){

        $count_tasks = (new RawQueries())->countActiveTask();

        return static::response(null,$count_tasks, 200, 'success');
    }

    public function countHiddenTask($request){

        $count_tasks = (new RawQueries())->countHiddenTask();

        return static::response(null,$count_tasks, 200, 'success');
    }

    public function countOwnTask($request){

        $count_tasks = (new RawQueries())->countOwnTask();

        return static::response(null,$count_tasks, 200, 'success');
    }

    public function countCompletedTask($request){

        $count_tasks = (new RawQueries())->countCompletedTask();

        return static::response(null,$count_tasks, 200, 'success');
    }

    public function allTaskSearch($request){
        $search_key = $request->search_key;
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $list = [];
        if($search_key == ""){
            return static::response('Please input search key!', null, 401, 'error');
        }

        $archived_tasks = TaskDeleted::where('status',1)->pluck('task_id');
        $task_query = Task::select(['tasks.title','tasks.description','tasks.id',
                            'tasks.slug','tasks.created_at','tasks.expired_date',
                            'tasks.reward','tasks.total_point','tasks.category',
                            'tasks.task_link','tasks.total_rewards','tasks.final_cost',
                            'tasks.user_id','tasks.status','u.name','u.username'])
                            ->leftJoin('users as u','u.id','=','tasks.user_id')
                            ->whereNotIn('tasks.id',$archived_tasks)
                            ->where(function($q) use ($search_key){
                                $q->where('title','LIKE', '%'.$search_key.'%')
                                  ->orWhere('slug','LIKE', '%'.$search_key.'%')
                                  ->orWhere('u.name','LIKE', '%'.$search_key.'%');
                            });

        $task_count = $task_query->count();
        $tasks = $task_query->orderByDesc('tasks.created_at')->skip($offset)->take($limit)->get();

        $list['count'] = $task_count;
        $list['data'] = $tasks;

        if( $task_count > 0){
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched', null, 200, 'success');
    }

    function getTaskFeeCharge($request){
        $fee_charge = static::taskFeeCharge();
        return static::response(null,$fee_charge, 201, 'success');
    }

    function getRequirementLimitation($request){

        $limitation = static::taskRequirementLimitation();
        
        return static::response(null,static::responseJwtEncoder($limitation), 201, 'success');
    }

    function getFreeTaskCount($request){

        $user_limitations = static::userAccessLimitations();

        return static::response(null,$user_limitations['free_task'], 201, 'success');
    }

    public function available_for_bot($req)
    {
        $limit = $req->has('limit') ? $req->limit : 50;
        $date = $req->has('created_at') ? $req->created_at . ' 00:00:00' : null;
        $task_id = $req->has('task_id') ? $req->task_id : null;
        $category = $req->has('category') ? $req->category : null;

        $query = Task::query();
        $lastid = Task::query();

        $lastid = $lastid->where('expired_date', '>=', Carbon::now())
            ->where('final_cost','>',0)
            ->where('status','1')
            ->orderBy('id','desc')
            ->limit(1);

        $query = $query->with(['user' => function($qry){
                $qry->select(['id','name','username','email', 'type']);
            }])
            ->where('expired_date', '>=', Carbon::now())
            ->where('final_cost','>',0)
            ->where('status','1')
            ->limit($limit);

        if($date){
            $query = $query->whereBetween('created_at',[$date,Carbon::now()]);
            $lastid = $lastid->whereBetween('created_at',[$date,Carbon::now()]);
        }
        if($task_id){
            $query = $query->where('id','>=',$task_id);
            $lastid = $lastid->where('id','>=',$task_id);
        }
        if($category){
            $query = $query->where('category', $category);
            $lastid = $lastid->where('category', $category);
        }

        $tasks = $query->get()->toArray();
        $last = $lastid->get(['id'])->makeHidden(['user_info','status_str','available_completer'])->toArray();

        $last = array_map(function($item){
            return $item['id'];
        },$last);

        $tasks = array_map(function($task){
            $val['id'] = $task['id'];
            $val['task_link'] = $task['task_link'];
            $val['task_status'] = 'ACTIVE';
            $val['created_at'] = $task['created_at'];
            $val['category'] = $task['category'];
            $val['reward'] = $task['reward'];
            $val['total_point'] = $task['total_point'];
            $val['total_reward'] = ($task['reward'] * $task['total_point']);
            $user = json_encode($task['user'], JSON_UNESCAPED_UNICODE);
            $user = json_decode($user, false, 512, JSON_UNESCAPED_UNICODE);
            $val['user'] = $user;
            return $val;
        },$tasks);

        return response()->json([
           'status' => 200,
           'last_id' => ($last) ? $last[0] : 0,
           'data' => $tasks
        ]);
    }

    public function related_for_bot($req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required',
            'taskid' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $tasks = Task::with(['user','completer'])
            ->where('tasks.expired_date', '>=', Carbon::now())
            ->where('tasks.status','<>','0')
            ->where('tasks.final_cost','>','0')
            ->where('tasks.user_id','=',$req->id)
            ->where('tasks.id','<>',$req->taskid)
            ->inRandomOrder()
            ->get();
        $count = count($tasks);
        return ['task'=>$tasks,'count'=>$count];
    }
}