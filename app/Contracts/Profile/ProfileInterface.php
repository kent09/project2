<?php

namespace App\Contracts\Profile;


interface ProfileInterface
{
    public function profileMainInfo($request);

    public function getSocialConnected($request);

    public function getSocialConnectionStatus($request);

    public function updateAccount($request);

    public function updatePassword($request);

    public function updateProfileImage($request);

    public function getAllFollowers($request);

    public function getAllFollowing($request);

    public function getAllConnections($request);

    public function getAllBlockedUsers($request);

    public function getLoginHistory($request);

    public function searchLoginHistory($request);

    public function saveSelfie($request);

    public function getVerified($request);

    public function getTimeline($request);

    public function toggleBlockUsers($request);

    public function toggleFollowUsers($request);

    public function toggleSocialLink($request);

    public function profileTaskActive($request);
    
    public function countFollowers($request);

    public function countFollowing($request);

    public function countConnections($request);

    public function getReputationScore($request);

    public function getActivityScore($request);

    public function userVerificationList($request);

    public function getProfileImage($user_id);

    public function giftSuperiorCoin($request);

    public function checkIsFollower($request);

    public function getFbProfilePictures($request);

    public function saveOwnReferrer($request);
}