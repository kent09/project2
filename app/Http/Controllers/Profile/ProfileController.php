<?php

namespace App\Http\Controllers\Profile;

use App\Contracts\Profile\ProfileInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected $profile;
    protected $request;

    public function __construct(ProfileInterface $profile, Request $request)
    {
        $this->profile = $profile;
        $this->request = $request;
    }
    

     /**
     * @SWG\POST(
     *     path="/api/profile/info",
     *     tags={"PROFILE-API"},
     *     summary="Profile Information",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load user profile!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function profileMainInfo()
    {
        return $this->profile->profileMainInfo($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/social-connect",
     *     tags={"PROFILE-API"},
     *     summary="View Social Connections",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load social media connections!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSocialConnected()
    {
        return $this->profile->getSocialConnected($this->request);
    }

    
      /**
     * @SWG\POST(
     *     path="/api/profile/social-connect-history",
     *     tags={"PROFILE-API"},
     *     summary="View Social Connection History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load social connection history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSocialConnectHistory()
    {
        return $this->profile->getSocialConnectHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/social-connect-status",
     *     tags={"PROFILE-API"},
     *     summary="Social Media Connection Status",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load social media connections!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSocialConnectionStatus()
    {
        return $this->profile->getSocialConnectionStatus($this->request);
    }
     /**
     * @SWG\POST(
     *     path="/api/profile/update-account",
     *     tags={"PROFILE-API"},
     *     summary="Update User Profile",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="name", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="about", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="location", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="country", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated profile!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateAccount(){
        return $this->profile->updateAccount($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/update-password",
     *     tags={"PROFILE-API"},
     *     summary="Update User Password",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="cur_password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="new_password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated password!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updatePassword(){
        return $this->profile->updatePassword($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/all-followers",
     *     tags={"PROFILE-API"},
     *     summary="View User Followers",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated password!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function allFollowers(){
        return $this->profile->getAllFollowers($this->request);
    }


     /**
     * @SWG\POST(
     *     path="/api/profile/all-following",
     *     tags={"PROFILE-API"},
     *     summary="View Followed users",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded all followed users!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function allFollowing(){
        return $this->profile->getAllFollowing($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/all-connections",
     *     tags={"PROFILE-API"},
     *     summary="View User connections",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded all connections!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function allConnections()
    {
        return $this->profile->getAllConnections($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/all-blocked",
     *     tags={"PROFILE-API"},
     *     summary="View All blocked users",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded all blocked users!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function allBlockedUsers()
    {
        return $this->profile->getAllBlockedUsers($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/login-history",
     *     tags={"PROFILE-API"},
     *     summary="Login History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded login history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getLoginHistory(){
        return $this->profile->getLoginHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/login-history-search",
     *     tags={"PROFILE-API"},
     *     summary="Search login History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded login history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function searchLoginHistory(){
        return $this->profile->searchLoginHistory($this->request);
    }

    public function saveSelfie(){
        return $this->profile->saveSelfie($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/get-verified",
     *     tags={"PROFILE-API"},
     *     summary="Get Account Verified",
     *     @SWG\Parameter(
     *      name="password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="passport_id", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filepath", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully verified account!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getVerified(){
        return $this->profile->getVerified($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/resend-verification",
     *     tags={"PROFILE-API"},
     *     summary="Resend Account Verification",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Resending Emeil Verification Success!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function resendVerification(){
        return $this->profile->resendVerification($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/timeline",
     *     tags={"PROFILE-API"},
     *     summary="View User Timeline",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="sort", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded timeline!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getTimeline()
    {
        return $this->profile->getTimeline($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/generate-steemit-footer",
     *     tags={"PROFILE-API"},
     *     summary="Generate Steemit Footer",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully generated steemit footer!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function generateSteemitFooter()
    {
        return $this->profile->generateSteemitFooter($this->request);
    }

    
    /**
     * @SWG\POST(
     *     path="/api/profile/toggle-block-user",
     *     tags={"PROFILE-API"},
     *     summary="Toggle Block/Unblock User",
     *     @SWG\Parameter(
     *      name="blocker_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="blocked_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="status", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully blocked/unblocked user!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function toggleBlockUsers()
    {
        return $this->profile->toggleBlockUsers($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/toggle-follow-user",
     *     tags={"PROFILE-API"},
     *     summary="Toggle Follow/Unfollow User",
     *     @SWG\Parameter(
     *      name="follower_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="followed_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="status", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully followed/unfollowed user!"),
     *     @SWG\Response(response=401, description="Failed to follow/unfollower user!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function toggleFollowUsers()
    {
        return $this->profile->toggleFollowUsers($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/toggle-social-link",
     *     tags={"PROFILE-API"},
     *     summary=" Toggle Link/Unlink Social Media Account",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="social", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="status", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully linked/unlinked user!"),
     *     @SWG\Response(response=401, description="Failed to link/unlink user!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function toggleSocialLink()
    {
        return $this->profile->toggleSocialLink($this->request);
    }
   
    /**
     * @SWG\POST(
     *     path="/api/profile/count-followers",
     *     tags={"PROFILE-API"},
     *     summary="Count Followers",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded number of followers!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countFollowers()
    {
        return $this->profile->countFollowers($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/count-following",
     *     tags={"PROFILE-API"},
     *     summary="Count Following",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded number of following!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countFollowing()
    {
        return $this->profile->countFollowing($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/count-connections",
     *     tags={"PROFILE-API"},
     *     summary="Count connections",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded number of connections!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countConnections()
    {
        return $this->profile->countConnections($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/reputation-score",
     *     tags={"PROFILE-API"},
     *     summary="Get Reputation Score",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded reputation score!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getReputationScore()
    {
        return $this->profile->getReputationScore($this->request);
    }
    

     /**
     * @SWG\POST(
     *     path="/api/profile/activity-score",
     *     tags={"PROFILE-API"},
     *     summary="Get Activity Score",
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded activity score!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getActivityScore()
    {
        return $this->profile->getActivityScore($this->request);
    }
    
    
    public function profileTaskActive() {
        return $this->profile->profileTaskActive($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/profile/update-profile-image",
     *     tags={"PROFILE-API"},
     *     summary="Update Profile Image",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="image", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated profile image!"),
     *     @SWG\Response(response=401, description="Failed to update profile image!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateProfileImage() {
        return $this->profile->updateProfileImage($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/profile/user-verification",
     *     tags={"PROFILE-API"},
     *     summary="User Verification",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded user verification list!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function userVerificationList() {
        return $this->profile->userVerificationList($this->request);
    }

    /**
     * @SWG\GET(
     *     path="/api/profile/image/{user_id}",
     *     tags={"PROFILE-API"},
     *     summary="Profile Image",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully returned user profile image!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getProfileImage($user_id) {
        return $this->profile->getProfileImage($user_id);
    }
   
    /**
     * @SWG\POST(
     *     path="/api/profile/gift-superior-coin",
     *     tags={"PROFILE-API"},
     *     summary="Gift Superior Coin",
     *     @SWG\Parameter(
     *      name="gift_recipient_id", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="gift_coin", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="gift_memo", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully processed gift coin!"),
     *     @SWG\Response(response=401, description="Failed to give process gift coin!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function giftSuperiorCoin() {
        return $this->profile->giftSuperiorCoin($this->request);
    }
   
   public function checkIsFollower(){
        return $this->profile->checkIsFollower($this->request);
   }

   /**
     * @SWG\POST(
     *     path="/api/profile/unblock-user",
     *     tags={"PROFILE-API"},
     *     summary="Unblock user",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="task_user_id", in="formData", required=true, type="integer"
     *      ),
     *     
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully unblocked user!"),
     *     @SWG\Response(response=401, description="Failed to unblock user!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
   public function unblockUser(){
       return $this->profile->unblockUser($this->request);
   }

   public function getFbProfilePictures(){
        return $this->profile->getFbProfilePictures($this->request);
   }

   public function saveOwnReferrer(){
    return $this->profile->saveOwnReferrer($this->request);
}
}
