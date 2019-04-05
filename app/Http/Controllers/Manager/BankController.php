<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Manager\BankInterface;

class BankController extends Controller
{

    protected $request, $bank;

    public function __construct(BankInterface $bank, Request $request)
    {
        $this->request = $request;
        $this->bank = $bank;
    }

    /**
     * @SWG\GET(
     *     path="/api/manager/bank/settings",
     *     tags={"ADMIN-API"},
     *     summary="Bank Settings",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bank settings!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function index(){
        return $this->bank->index();
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/bank/sup-for-approval",
     *     tags={"ADMIN-API"},
     *     summary="Bank SUP For Approval Withdrawals List",
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
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded SUP For Approval Withdrawals!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function supForApproval(){
        return $this->bank->supForApproval($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/bank/btc-for-approval",
     *     tags={"ADMIN-API"},
     *     summary="Bank BTC For Approval Withdrawals List",
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
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded BTC For Approval Withdrawals!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcForApproval(){
        return $this->bank->btcForApproval($this->request);
    }

    
    /**
     * @SWG\POST(
     *     path="/api/manager/bank/withdrawal/sup/approve/{id}",
     *     tags={"ADMIN-API"},
     *     summary="Approve SUP withdrawal",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully approved SUP withdrawal!"),
     *     @SWG\Response(response=401, description="Failed to approve SUP withdrawal!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function approveSupWithdrawal($id){
        $data = ['id' => $id, 'status' => 1];
        return $this->bank->setSupWithdrawalStatus($data);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/bank/withdrawal/sup/decline/{id}",
     *     tags={"ADMIN-API"},
     *     summary="Decline SUP withdrawal",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully declined SUP withdrawal!"),
     *     @SWG\Response(response=401, description="Failed to decline SUP withdrawal!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function declineSupWithdrawal($id){
        $data = ['id' => $id, 'status' => 11];
        return $this->bank->setSupWithdrawalStatus($data);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/bank/withdrawal/btc/approve/{id}",
     *     tags={"ADMIN-API"},
     *     summary="Approve BTC withdrawal",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully approved BTC withdrawal!"),
     *     @SWG\Response(response=401, description="Failed to approve BTC withdrawal!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function approveBtcWithdrawal($id){
        $data = ['id' => $id, 'status' => 1];
        return $this->bank->setBtcWithdrawalStatus($data);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/bank/withdrawal/btc/decline/{id}",
     *     tags={"ADMIN-API"},
     *     summary="Decline BTC withdrawal",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully declined BTC withdrawal!"),
     *     @SWG\Response(response=401, description="Failed to decline BTC withdrawal!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function declineBtcWithdrawal($id){
        $data = ['id' => $id, 'status' => 11];
        return $this->bank->setBtcWithdrawalStatus($data);
    }

    /**
     * @SWG\POST(
     *     path="/api/manager/task/revoke-list/{task_id}",
     *     tags={"ADMIN-API"},
     *     summary="Task Revoke List",
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
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully laoded task revoke list"),
     *     @SWG\Response(response=401, description="Failed to load task revoke list!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskRevokeList(){
        return $this->bank->taskRevokeList($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/manager/task/creator-stats",
     *     tags={"ADMIN-API"},
     *     summary="Task Creator Stats",
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
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully laoded task creator stats"),
     *     @SWG\Response(response=401, description="Failed to load task creator stats!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskCreatorStats(){
        return $this->bank->taskCreatorStats($this->request);
    }

    
     /**
     * @SWG\POST(
     *     path="/api/manager/task/reinstate-reward",
     *     tags={"ADMIN-API"},
     *     summary="Task Creator Stats",
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
     *     @SWG\Response(response=200, description="Successfully reinstated reward!"),
     *     @SWG\Response(response=401, description="Failed to reinstate reward!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function reinstateReward(){
        return $this->bank->reinstateReward($this->request);
    }
}
