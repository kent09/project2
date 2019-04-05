<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Manager\ReferralInterface;

class ReferralController extends Controller
{

    protected $request, $referral;


    public function __construct(ReferralInterface $referral, Request $request)
    {
        $this->request = $request;
        $this->referral = $referral;
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/referral/settings",
     *     tags={"ADMIN-API"},
     *     summary="Referral Settings",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded referral settings!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function index(){
        return $this->referral->index();
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/referral/set-settings",
     *     tags={"ADMIN-API"},
     *     summary="Set Referral Settings",
     *     @SWG\Parameter(
     *      name="key", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="value", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully set referral settings!"),
     *     @SWG\Response(response=401, description="Failed to set referral settings!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function setReferralSettings()
    {
        return $this->referral->setReferralSettings($this->request);
    }


     /**
     * @SWG\GET(
     *     path="/api/manager/referral/task-point/settings-history",
     *     tags={"ADMIN-API"},
     *     summary="Referral Settings History",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded Referral History Settings!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskPointSettingsHistory(){
        return $this->referral->taskPointSettingsHistory($this->request);
    }

     /**
     * @SWG\GET(
     *     path="/api/manager/referral/signup-reward/settings-history",
     *     tags={"ADMIN-API"},
     *     summary="Signup Reward Settings History",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded signup reward settings history!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function signupRewardSettingsHistory(){
        return $this->referral->signupRewardSettingsHistory($this->request);
    }



     /**
     * @SWG\GET(
     *     path="/api/manager/referral/social-connect/settings-history",
     *     tags={"ADMIN-API"},
     *     summary="Social Connect Reward Settings History",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded social connect reward settings history!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function socialConnectSettingsHistory(){
        return $this->referral->socialConnectSettingsHistory($this->request);
    }

     /**
     * @SWG\GET(
     *     path="/api/manager/referral/change-request",
     *     tags={"ADMIN-API"},
     *     summary="Referral Change Request",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="new_referrer_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully sent request for referral change!"),
     *     @SWG\Response(response=401, description="Failed to send request for referral change!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referralChangeRequest(){
        return $this->referral->referralChangeRequest($this->request);
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/referral/approve-referral-change",
     *     tags={"ADMIN-API"},
     *     summary="Approve Referral Change Request",
     *     @SWG\Parameter(
     *      name="referral_req_id", in="formData", required=true, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="admin_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully approved referral change!"),
     *     @SWG\Response(response=401, description="Failed to approve referral change!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function approveReferralChange(){
        return $this->referral->approveReferralChange($this->request);
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/referral/decline-referral-change",
     *     tags={"ADMIN-API"},
     *     summary="Decline Referral Change Request",
     *     @SWG\Parameter(
     *      name="referral_req_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="admin_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully declined referral change!"),
     *     @SWG\Response(response=401, description="Failed to decline referral change!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function declineReferralChange(){
        return $this->referral->declineReferralChange($this->request);
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/referral/change-request-list",
     *     tags={"ADMIN-API"},
     *     summary="Referral Change Request List",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded referral change request list!"),
     *     @SWG\Response(response=401, description="Failed to load referral change request list!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referralChangeRequestList(){
        return $this->referral->referralChangeRequestList($this->request);
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/referral/change-history",
     *     tags={"ADMIN-API"},
     *     summary="Referral Change History",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded referral change history!"),
     *     @SWG\Response(response=401, description="Failed to load referral change history!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referralChangeHistory(){
        return $this->referral->referralChangeHistory($this->request);
    }

}