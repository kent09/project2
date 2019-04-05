<?php

namespace App\Repository;

use App\Contracts\NotificationInterface;
use App\Traits\UtilityTrait;
use App\Traits\Manager\UserTrait;
use App\Model\Notification;
use App\Model\PushNotification;
use App\User;
use Carbon\Carbon;
use Auth;

class NotificationRepository implements NotificationInterface
{
    use UtilityTrait, UserTrait;

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userNofification($request) {
        $user_id = Auth::id();
        $list = [];
        $user = User::find($user_id);
        $limit = $request->has('limit') ? $request->limit : 10; 
        $offset = $request->has('offset') ? $request->offset : 0;
       
       $notification = $user->notifications()->where('is_deleted', 0)->orderBy('created_at', 'desc')->offset($offset)
                    ->limit($limit)
                    ->get();

        if(count($notification) > 0){
            foreach($notification as $key => $notif){
                $sender_info = static::get_user($notif->sender_id);
                $item = [
                    'notif_id' => $notif->id,
                    'notif_datetime' => Carbon::parse($notif->created_at)->diffForHumans(),
                    'title' => $notif->title,
                    'description' => $notif->description,
                    'type' => $notif->type,
                    'sender_id' => $notif->sender_id,
                    'sender_name' => $sender_info->name,
                    'sender_username' => $sender_info->username,
                    'recipient_id' => $notif->recipient_id,
                ];
                array_push($list, $item);
            }
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'error');
    }

    public function countAllNotifications($request) {
        $user_id = Auth::id();
        $user = User::find($user_id);
        $count = $user->notifications()->where('is_deleted', 0)->count();
        if($count){
           
            return static::response(null,static::responseJwtEncoder($count), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'error');
    }

    

    public function showAll(){
        $notifications = Notification::orderBy('status', 'asc')->get();
            if(count($notifications) > 0)
            return static::response(null,$notifications, 201, 'success');
        return static::response('No Data Fetched', null, 200, 'success');
    }

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkReadStatusProperty($request){
        $unread = [];
        $data = [];
        $user_id = Auth::id();

        $notifications = Notification::where('recipient_id', $user_id)->where('is_deleted', 0)->get();

        if(count($notifications)){
            foreach ($notifications as $notification) {

                if($notification->status == 0) {
                    array_push($unread, 1);
                }
            }
    
            if(array_sum($unread) <= 0) {
    
                $item = [
                    'type' => 'info',
                    'message' => 'No available unread notification to be set!',
                    'class' => 1 //no unread notification
                ];
            }elseif(array_sum($unread) > 0) {
    
                $item = [
                    'type' => 'info',
                    'message' => array_sum($unread) . ' available unread notification to be set!',
                    'class' => 2 //available unread notification
                ];
            }else{
                $item = [
                    'type' => 'error',
                    'message' => 'Error, Something went wrong.',
                    'class' => 3 //available unread notification
                ];
            }
    
            array_push($data, $item);
    
            return static::response(null,$data, 201, 'success');

        }else{
            return static::response('No Data Fetched', null, 200, 'success');
        }
    }

     /**
     * @param $request [notif_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNotification($request){
        $model = new Notification();
        $notif_id = $request->notif_id;

        $deleteNotif = ($model)->deleteNotification($notif_id);

        if($deleteNotif){
            return static::response('Successfully deleted Notification!',null, 201, 'success');
        }else{
            return static::response('No Data Fetched', null, 200, 'success');
        }
    }

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function setEmailNotification($request){
        $user_id = Auth::id();

        $email_notification = PushNotification::where('user_id', $user_id )->first();
       
      
       if ($email_notification != null) {
            $insert_email = json_decode($email_notification->options,true);
            $insert_email = array_merge($insert_email, ["email" => true]);
            $email_notification->user_id = $user_id;
            $email_notification->options = json_encode($insert_email);
            if($email_notification->save()){
                return static::response('Successfully set email Notification!',null, 201, 'success');
            }
 
       }else{
            $push_notification = new PushNotification;
            $insert_email[] = "email";
            $push_notification->user_id = $user_id;
            $push_notification->options = json_encode($insert_email);
            if($push_notification->save()){
                return static::response('Successfully set email Notification!',null, 201, 'success');
            }
       }
       return static::response('No Data Fetched', null, 200, 'success');
    }


     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeEmailNotification($request)
    {
        $user_id = Auth::id();

        $email_notification = PushNotification::where('user_id',  $user_id )->first();
        if($email_notification == null){
            $push_notification = new PushNotification;
            $insert_email[] = "";
            $push_notification->options = json_encode($insert_email);
            $push_notification->user_id = $user_id;
            if($push_notification->save()){
                return static::response('Successfully removed email Notification!',null, 201, 'success');
            }
        }else{
            $unset_email = json_decode($email_notification->options,true);
            $test = array_search('email', $unset_email);
              if($test){
                unset($unset_email["email"]);
            }
            $email_notification->user_id = $user_id;
            $email_notification->options = json_encode($unset_email);
            if($email_notification->save()){
                return static::response('Successfully removed email Notification!',null, 201, 'success');
            }
        }

        return static::response('No Data Fetched', null, 200, 'success');
    }
}