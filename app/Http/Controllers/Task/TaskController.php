<?php

namespace App\Http\Controllers\Task;

use App\Contracts\Task\TaskInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TaskController extends Controller
{
    protected $request;
    protected $task;
    public function __construct(TaskInterface $task, Request $request)
    {
        $this->task = $task;
        $this->request = $request;
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-list",
     *     tags={"TASK-API"},
     *     summary="List of Active Task",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load Tasks"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */

    public function index() {
        return $this->task->index($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-own-list",
     *     tags={"TASK-API"},
     *     summary="Authenticated User List Of Tasks",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load Tasks"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function ownTask() {
        return $this->task->ownTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-hidden",
     *     tags={"TASK-API"},
     *     summary="Authenticated User List Of Hidden Tasks",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load Hidden Tasks"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hiddenTask() {
        return $this->task->hiddenTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-completed",
     *     tags={"TASK-API"},
     *     summary="Authenticated User List Of Completed Tasks",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load Completed Tasks"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function completedTask() {
        return $this->task->completedTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-unhide",
     *     tags={"TASK-API"},
     *     summary="Un-hide currently hidden task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Task successfully un-hide"),
     *     @SWG\Response(response=401, description="Kryptonia encounter server error, please reload the page"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function unHideTask() {
        return $this->task->unHideTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-delete",
     *     tags={"TASK-API"},
     *     summary="Deactivate current task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Task successfully set to archived"),
     *     @SWG\Response(response=400, description="Kryptonia forbid your free first task to be deleted!"),
     *     @SWG\Response(response=401, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteTask() {
        return $this->task->deleteTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-activate",
     *     tags={"TASK-API"},
     *     summary="Activate in-active task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Task successfully updated"),
     *     @SWG\Response(response=400, description="Error, You Have Insufficient SUP!"),
     *     @SWG\Response(response=401, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=402, description="Error, while updating the task!, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function activateTask() {
        return $this->task->activateTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-deactivate",
     *     tags={"TASK-API"},
     *     summary="Deactivate active task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="This task successfully deactivated"),
     *     @SWG\Response(response=401, description="Error!, Something went wrong please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deActivateTask() {
        return $this->task->deActivateTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-create",
     *     tags={"TASK-API"},
     *     summary="Create a task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="title", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="description", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="task_url", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="reward", in="formData", required=true, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="completer", in="formData", required=true, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="task_image", in="formData", required=false, type="file"
     *      ),
     *     @SWG\Parameter(
     *      name="from_wizard", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="task_category", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="follower_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="reputation_option", in="formData", required=false, type="boolean",
     *      description="If set to true input the minimum number either in activity score or reputation or both otherwise leave it blank."
     *      ),
     *     @SWG\Parameter(
     *      name="activity_score", in="formData", required=false, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="reputation", in="formData", required=false, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="connection_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="task_completion_attachment_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Your Task Is Successfully Created"),
     *     @SWG\Response(response=401, description="Error, Not enough Superior Coin!"),
     *     @SWG\Response(response=402, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function createTask() {
        return $this->task->createTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-edit",
     *     tags={"TASK-API"},
     *     summary="Edit active task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=401, description="Error, Unable to find task!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function editTask() {
        return $this->task->editTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-update",
     *     tags={"TASK-API"},
     *     summary="Update a task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="title", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="description", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="task_url", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="reward", in="formData", required=true, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="completer", in="formData", required=true, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="task_image", in="formData", required=false, type="file"
     *      ),
     *     @SWG\Parameter(
     *      name="from_wizard", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="task_category", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="follower_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="reputation_option", in="formData", required=false, type="boolean",
     *      description="If set to true input the minimum number either in activity score or reputation or both otherwise leave it blank."
     *      ),
     *     @SWG\Parameter(
     *      name="activity_score", in="formData", required=false, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="reputation", in="formData", required=false, type="number"
     *      ),
     *     @SWG\Parameter(
     *      name="connection_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="task_completion_attachment_option", in="formData", required=false, type="boolean"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Success, task successfully updated"),
     *     @SWG\Response(response=401, description="Error, Not enough Superior Coin!"),
     *     @SWG\Response(response=402, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateTask() {
        return $this->task->updateTask($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-show",
     *     tags={"TASK-API"},
     *     summary="Show active task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=401, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function showTask() {
        return $this->task->showTask($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-link-image-tag",
     *     tags={"TASK-API"},
     *     summary="get task link image metatag for preview",
     *     @SWG\Parameter(
     *      name="url", in="formData", required=true, type="string"
     *      ),
     *   
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=401, description="Kryptonia encounter server error, please reload the page!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getImageMetatag(){
        return $this->task->getImageMetaTag($this->request);
    }
    /**
     * @SWG\POST(
     *     path="/api/task/task-complete",
     *     tags={"TASK-API"},
     *     summary="Complete active task",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Task is successfully completed!"),
     *     @SWG\Response(response=401, description="Error, Something went wrong while completing the task!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function completeTask() {
        return $this->task->completeTask($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-list-search",
     *     tags={"TASK-API"},
     *     summary="Search Active task",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="category_filter", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load specific Tasks!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function searchTaskList() {
        return $this->task->searchTaskList($this->request);
    }

     public function taskDetails() {
        return $this->task->taskDetails($this->request);
    }

    public function generateTaskUrl() {
        return $this->task->generateTaskUrl($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-completer-list",
     *     tags={"TASK-API"},
     *     summary="Task Completer List",
     *      @SWG\Parameter(
     *      name="slug", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load task completer list!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskCompleterList() {
        return $this->task->taskCompleterList($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-revoke-completer-list",
     *     tags={"TASK-API"},
     *     summary="Task Completer List",
     *      @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load task revoke completer list!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskRevokeCompleterList() {
        return $this->task->taskRevokeCompleterList($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-comments",
     *     tags={"TASK-API"},
     *     summary="Task Comments",
     *      @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load task comments!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskComments() {
        return $this->task->taskComments($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-comment-specific",
     *     tags={"TASK-API"},
     *     summary="Task Comments",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="comment_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load task comments!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function specificTaskComment() {
        return $this->task->specificTaskComment($this->request);
    }


    /**
     * @SWG\POST(
     *     path="/api/task/task-comment-upload",
     *     tags={"TASK-API"},
     *     summary="Task Comment Upload Image",
     *     @SWG\Parameter(
     *      name="comment_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="type", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="task_comment_img", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully uploaded image in comment!"),
     *     @SWG\Response(response=401, description="Error, Failed to upload image!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskCommentUploadImage() {
        return $this->task->taskCommentUploadImage($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-comment-save",
     *     tags={"TASK-API"},
     *     summary="Task Comment Posting",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="comment", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully posted comment!"),
     *     @SWG\Response(response=401, description="Error, Failed to post comment!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function saveTaskComment() {
        return $this->task->saveTaskComment($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-comment-reply-save",
     *     tags={"TASK-API"},
     *     summary="Task Comment Reply Posting",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="comment_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="comment", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully posted comment!"),
     *     @SWG\Response(response=401, description="Error, Failed to post comment!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function saveTaskSubComment() {
        return $this->task->saveTaskSubComment($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-comment-update",
     *     tags={"TASK-API"},
     *     summary="Task Update Comment",
     *     @SWG\Parameter(
     *      name="type", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="comment_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="comment", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated comment!"),
     *     @SWG\Response(response=401, description="Error, Failed to update comment!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateTaskComment() {
        return $this->task->updateTaskComment($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-comment-delete",
     *     tags={"TASK-API"},
     *     summary="Task Comment Deletion",
     *     @SWG\Parameter(
     *      name="type", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="task_comment_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully removed comment!"),
     *     @SWG\Response(response=401, description="Error, Failed to remove comment!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteTaskComment() {
        return $this->task->deleteTaskComment($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-comment-count",
     *     tags={"TASK-API"},
     *     summary="Task Count Comments",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded number of comments of this task!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countTaskComments() {
        return $this->task->countTaskComments($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/revoke-user",
     *     tags={"TASK-API"},
     *     summary="Revoke User Task",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="completer_user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Revoked From Task!"),
     *     @SWG\Response(response=401, description="Unable To Revoked From Task!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function revokeUserTask() {
        return $this->task->revokeUserFromTask($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/block-user",
     *     tags={"TASK-API"},
     *     summary="Revoke User Task",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="completer_user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Blocked And Revoked From All Your Task!"),
     *     @SWG\Response(response=401, description="Unable To Block And Revoke From Your Task!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function blockUserTask() {
        return $this->task->blockUserFromTask($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-hide",
     *     tags={"TASK-API"},
     *     summary="Hide User task",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Hide Task!"),
     *     @SWG\Response(response=401, description="Unable hide Task!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hideTask() {
        return $this->task->hideTask($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/task/task-history",
     *     tags={"TASK-API"},
     *     summary="Task History",
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *    @SWG\Parameter(
     *      name="category", in="formData", required=false, type="string"
     *      ),
     *    @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded task history!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskHistory(){
        return $this->task->taskHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/task/task-attachment-view",
     *     tags={"TASK-API"},
     *     summary="View Task Attachment",
     *     @SWG\Parameter(
     *      name="task_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded task attachment!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function viewTaskAttachment(){
        return $this->task->viewTaskAttachment($this->request);
    }

    
     /**
     * @SWG\POST(
     *     path="/api/landing/task/get-featured-task-creator",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Get Featured Task Creators",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded featured task creators!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getFeaturedTaskCreator(){
        return $this->task->getFeaturedTaskCreator($this->request);
    }

    
     /**
     * @SWG\POST(
     *     path="/api/landing/task/task-blocked-users",
     *     tags={"TASK-API"},
     *     summary="Get All Blocked Tasks User",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded featured task creators!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskBlockedUsers(){
        return $this->task->taskBlockedUsers($this->request);
    }

    public function countActiveTask(){
        return $this->task->countActiveTask();
    }

    public function countHiddenTask(){
        return $this->task->countHiddenTask($this->request);
    }

    public function countOwnTask(){
        return $this->task->countOwnTask($this->request);
    }

    public function countCompletedTask(){
        return $this->task->countCompletedTask($this->request);
    }

    public function allTaskSearch(){
        return $this->task->allTaskSearch($this->request);
    }

    public function getTaskFeeCharge(){
        return $this->task->getTaskFeeCharge($this->request);
    }

    public function getRequirementLimitation(){
        return $this->task->getRequirementLimitation($this->request);
    }

    public function getFreeTaskCount(){
        return $this->task->getFreeTaskCount($this->request);
    }

    public function available_for_bot(){
        return $this->task->available_for_bot($this->request);
    }

    public function related_for_bot(){
        return $this->task->related_for_bot($this->request);
    }
}
