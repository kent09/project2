<?php

namespace App\Contracts;


interface NotificationInterface
{
    public function userNofification($request);

    public function countAllNotifications($request);

    public function showAll();
    
    public function checkReadStatusProperty($request);

    public function deleteNotification($request);

    public function setEmailNotification($request);

    public function removeEmailNotification($request);
    
}