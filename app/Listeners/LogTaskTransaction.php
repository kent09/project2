<?php

namespace App\Listeners;

use App\User;
use App\Model\Notification;
use App\Model\UserReputationActivityScore;
use App\Events\NewTaskTransaction;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogTaskTransaction
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewTaskTransaction  $event
     * @return void
     */
    public function handle(NewTaskTransaction $event)
    {

        $data = [
            'task_user_id' => $event->task,
            'completer_user_id' => $event->user,
            'type' => $event->type,
        ];

        if($event->type == 'revoked' || $event->type == 'completed'){
            (new UserReputationActivityScore())->saveActivityScoreReputation($event->type, $event->user->id);
        }

        $this->logNewTaskTransaction($data);
    }

    private function logNewTaskTransaction($data = []) {

        $type = $data['type'];

        if($type == 'completed'){
            $sender_id = $data['completer_user_id']->id;
            $recipient_id = $data['task_user_id']->user_id;
            $title = 'Task Completed';
            $type_desc = 'Completed';

        }elseif($type == 'deleted'){
            $sender_id = $data['completer_user_id']->id;
            $recipient_id = $data['task_user_id']->user_id;
            $title = 'Task Deleted';
            $type_desc = 'Deleted';

        }elseif($type == 'attachment'){
            $sender_id = $data['completer_user_id']->id;
            $recipient_id = $data['task_user_id']->user_id;
            $title = 'Task Attachment';
            $type_desc = 'Attachment';

        }elseif($type == 'comment'){
            $sender_id = $data['completer_user_id']->id;
            $recipient_id = $data['task_user_id']->user_id;
            $title = 'Task Comment';
            $type_desc = 'Comment';

        }elseif($type == 'points'){
            $sender_id = $data['task_user_id']->user_id;
            $recipient_id = $data['completer_user_id']->id;
            $title = 'Task Points';
            $type_desc = 'Points';

        }else{
            $sender_id = $data['task_user_id']->user_id;
            $recipient_id = $data['completer_user_id']->id;
           
            if($type == 'revoked'){
                $title = 'Revoked';
                $type_desc = 'Revoked';
            }else{
                $title = 'Blocked';
                $type_desc = 'Blocked';
            }
        }

        $data = [
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'title' => $title,
            'description' => static::notifyDescription($data['type'], $data['completer_user_id'], $data['task_user_id']),
            'type' => $type_desc,
            'task_id' => 0
        ];

        return (new Notification())->saveTransaction($data);
    }

    private static function notifyDescription($type, $completer, $owner) {

        $owner_info = User::find($owner->user_id);
        $description = '';

        if($type === 'completed') {
            $description .= 'The task ' .strtoupper($owner->title).' was completed by ' . $completer->name;

        }else if($type == 'deleted'){
            $description .= 'The task ' .strtoupper($owner->title).' was deleted by ' . $completer->name;
            
        }else if($type == 'attachment'){
            $description .= $completer->name . ' attached screenshot to your task '. strtoupper($owner->title);
        
        }else if($type == 'comment'){
            $description .= $completer->name . ' added new comment on your task '. strtoupper($owner->title);

        }else if($type == 'points'){
            $description .= $owner_info->name . ' sent you '.$owner->reward. 'SUP for completing the task ' .strtoupper($owner->title);

        } else {
            if($type == 'revoked'){
                $description .= $owner_info->name . ' revoked you on a task '. strtoupper($owner->title);
            }else{
                $description .= $owner_info->name . ' revoked and blocked you on a task '.strtoupper($owner->title);
            }
        }

        return $description;
    }
}
