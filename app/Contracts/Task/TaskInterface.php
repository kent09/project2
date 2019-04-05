<?php


namespace App\Contracts\Task;


interface TaskInterface
{
    public function index($request);

    public function ownTask($request);

    public function hiddenTask($request);

    public function completedTask($request);

    public function hideTask($request);

    public function unHideTask($request);

    public function deleteTask($request);

    public function activateTask($request);

    public function deActivateTask($request);

    public function createTask($request);

    public function editTask($request);

    public function updateTask($request);

    public function showTask($request);

    public function completeTask($request);

    public function searchTaskList($request);

    public function taskDetails($request);

    public function generateTaskUrl($request);

    public function taskCompleterList($request);

    public function taskComments($request);

    public function specificTaskComment($request);

    public function taskCommentUploadImage($request);

    public function saveTaskComment($request);

    public function saveTaskSubComment($request);

    public function updateTaskComment($request);

    public function deleteTaskComment($request);

    public function countTaskComments($request);

    public function revokeUserFromTask($request);

    public function blockUserFromTask($request);

    public function taskHistory($request);

    public function viewTaskAttachment($request);

    public function getFeaturedTaskCreator($request);

    public function taskBlockedUsers($request);

    public function countActiveTask();

    public function countHiddenTask($request);

    public function countOwnTask($request);

    public function countCompletedTask($request);

    public function allTaskSearch($request);

    public function getTaskFeeCharge($request);

    public function getRequirementLimitation($request);

    public function getFreeTaskCount($request);

    public function available_for_bot($request);

    public function related_for_bot($request);
}