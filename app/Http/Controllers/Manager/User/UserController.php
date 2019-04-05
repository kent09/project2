<?php

namespace App\Http\Controllers\Manager\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Manager\User\UserInterface;

class UserController extends Controller
{
    protected $req;
    protected $user;

    public function __construct(UserInterface $user, Request $req)
    {
        $this->user = $user;
        $this->req = $req;
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/user/all-users",
     *     tags={"ADMIN-API"},
     *     summary="Get All Users",
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load all users!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function get_all_users()
    {
        return $this->user->get_all_users($this->req);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/user/filter-users",
     *     tags={"ADMIN-API"},
     *     summary="Get Filtered Users",
     *     @SWG\Parameter(
     *      name="status", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load filtered users!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function get_filtered_users()
    {
        return $this->user->get_filtered_users($this->req);
    }

    public function search()
    {
        return $this->user->search($this->req);
    }

    public function user_counts()
    {
        return $this->user->user_counts();
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/user/statistics",
     *     tags={"ADMIN-API"},
     *     summary="Get Statistics",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load user statistics!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getStatistics(){
        return $this->user->getStatistics();
    }

     /**
     * @SWG\GET(
     *     path="/api/manager/user/device-count",
     *     tags={"ADMIN-API"},
     *     summary="Device Count",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully load device count!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deviceCount(){
        return $this->user->deviceCount();
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/user/ban",
     *     tags={"ADMIN-API"},
     *     summary="Ban User",
     *    @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *    @SWG\Parameter(
     *      name="ban_type", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully banned user!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function banUser(){
        return $this->user->banUser($this->req);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/user/unban",
     *     tags={"ADMIN-API"},
     *     summary="Ban User",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully unbanned user!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function unbanUser(){
        return $this->user->unbanUser($this->req);
    }

     /**
     * @SWG\GET(
     *     path="/api/manager/user/accountSummary/{username}",
     *     tags={"ADMIN-API"},
     *     summary="Account Summary",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded user account summary!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function accountSummary($username){
        return $this->user->accountSummary($username);
    }

    
     /**
     * @SWG\GET(
     *     path="/api/manager/user/banned-reasons",
     *     tags={"ADMIN-API"},
     *     summary="Banned Reasons",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded user banned reasons!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function bannedReasons($user_id){
        return $this->user->bannedReasons($user_id);
    }

    
     /**
     * @SWG\POST(
     *     path="/api/manager/user/disable",
     *     tags={"ADMIN-API"},
     *     summary="Disable user account",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully disabled user account!"),
     *     @SWG\Response(response=401, description="Failed to disable user account!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function disableUser(){
        return $this->user->disableUser($this->req);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/user/activate",
     *     tags={"ADMIN-API"},
     *     summary="Activate user account",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully activated user account!"),
     *     @SWG\Response(response=401, description="Falled to activate user account!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function activateUser(){
        return $this->user->activateUser($this->req);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/user/activate-disable-multi",
     *     tags={"ADMIN-API"},
     *     summary="Activate/Disable Multiple Users",
     *     @SWG\Parameter(
     *      name="ids", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="status", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully activated/disabled user account/s!"),
     *     @SWG\Response(response=401, description="Falled to activate/disable user account/s!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function setStatusMulti(){
        return $this->user->setStatusMulti($this->req);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/user/ban-multi",
     *     tags={"ADMIN-API"},
     *     summary="Ban Multiple Users",
     *     @SWG\Parameter(
     *      name="ids", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully banned user account/s!"),
     *     @SWG\Response(response=401, description="Falled to ban user account/s!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function banUserMulti(){
        return $this->user->banUserMulti($this->req);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/user/unban-multi",
     *     tags={"ADMIN-API"},
     *     summary="Unban Multiple Users",
     *     @SWG\Parameter(
     *      name="ids", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully unbanned user account/s!"),
     *     @SWG\Response(response=401, description="Falled to unban user account/s!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function unbanUserMulti(){
        return $this->user->unbanUserMulti($this->req);
    }

    
    /**
     * @SWG\POST(
     *     path="/api/manager/social-connect/status-count",
     *     tags={"ADMIN-API"},
     *     summary="Social Connection Status Statistics",
     *     @SWG\Parameter(
     *      name="social", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded social connection status statictics!"),
     *     @SWG\Response(response=401, description="NO Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function countSocialConStatus(){
        return $this->user->countSocialConStatus($this->req);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/social-connect/all-list",
     *     tags={"ADMIN-API"},
     *     summary="Social Connect All List",
     *     @SWG\Parameter(
     *      name="social", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded social connection all list!"),
     *     @SWG\Response(response=401, description="NO Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function socialConnectAll(){
        return $this->user->socialConnectAll($this->req);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/social-connect/hard-unlink-request-list",
     *     tags={"ADMIN-API"},
     *     summary="Hard Unlink Request List",
     *     @SWG\Parameter(
     *      name="social", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded hard unlink request list!"),
     *     @SWG\Response(response=401, description="NO Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hardUnlinkRequestList(){
        return $this->user->hardUnlinkRequestList($this->req);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/social-connect/hard-unlinked-list",
     *     tags={"ADMIN-API"},
     *     summary="Hard Unlinked List",
     *     @SWG\Parameter(
     *      name="social", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded hard unlinked list!"),
     *     @SWG\Response(response=401, description="NO Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hardUnlinkedList(){
        return $this->user->hardUnlinkedList($this->req);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/social-connect/soft-unlinked-list",
     *     tags={"ADMIN-API"},
     *     summary="Soft Unlinked List",
     *     @SWG\Parameter(
     *      name="social", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="page", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="paginate", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded soft unlinked list!"),
     *     @SWG\Response(response=401, description="NO Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function softUnlinkedList(){
        return $this->user->softUnlinkedList($this->req);
    }
}
