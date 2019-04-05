<?php

namespace App\Http\Controllers\Notification;

use App\Contracts\NotificationInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class NotificationController extends Controller
{
    protected $notification;
    protected $request;

    public function __construct(NotificationInterface $notification, Request $request)
    {
        $this->notification = $notification;
        $this->request = $request;
    }
    
    /**
     * @SWG\POST(
     *     path="/api/notification/user",
     *     tags={"NOTIFICATION-API"},
     *     summary="View User notifications",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load user notifications!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function userNofification(){
        return $this->notification->userNofification($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/notification/count-all",
     *     tags={"NOTIFICATION-API"},
     *     summary="Count User notifications",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load user notifications!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countAllNotifications(){
        return $this->notification->countAllNotifications($this->request);
    }

    

    public function showAll(){
        return $this->notification->showAll();
    }

     /**
     * @SWG\POST(
     *     path="/api/notification/check-status",
     *     tags={"NOTIFICATION-API"},
     *     summary="Check Notification status",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load notification status!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function checkReadStatusProperty(){
        return $this->notification->checkReadStatusProperty($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/notification/delete",
     *     tags={"NOTIFICATION-API"},
     *     summary="Delete Notification",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="notif_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully deleted notification!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteNotification() {

        return $this->notification->deleteNotification($this->request);
    }


     /**
     * @SWG\POST(
     *     path="/api/notification/profile/notifications-email",
     *     tags={"NOTIFICATION-API"},
     *     summary="Set Email Notification",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully set email notification!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function setEmailNotification() {

        return $this->notification->setEmailNotification($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/notification/profile/unset-notifications-email",
     *     tags={"NOTIFICATION-API"},
     *     summary="Remove Email Notification",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully removed email notification!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function removeEmailNotification() {

        return $this->notification->removeEmailNotification($this->request);
    }

}
