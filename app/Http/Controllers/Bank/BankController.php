<?php

namespace App\Http\Controllers\Bank;

use App\Contracts\Bank\BankInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BankController extends Controller
{
    //
    protected $bank;
    protected $request;
    public function __construct(BankInterface $bank, Request $request)
    {
        $this->bank = $bank;
        $this->request = $request;
    }

    public function index() {
        return $this->bank->index();
    }

    public function withdraw()
    {
        return $this->bank->withdraw($this->request);
    }

    public function confirm_withdrawal($key)
    {
        return $this->bank->confirm_withdrawal($key);
    }

    public function cancel_withdraw()
    {
        return $this->bank->cancel_withdraw($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/resync",
     *     tags={"BANK-API"},
     *     summary="Resync Bank Balance",
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
     *     @SWG\Response(response=200, description="Successfully resynced bank balance!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function resync()
    {
        return $this->bank->resync($this->request);
    }

    public function request_resync()
    {
        return $this->bank->request_resync($this->request);
    }

    public function bitcoinWithdraw()
    {
        return $this->bank->bitcoinWithdraw($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/deposit",
     *     tags={"BANK-API"},
     *     summary="Deposit Basic History (Superior Coin)",
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
     *     @SWG\Response(response=200, description="Successfully loaded deposit basic history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function depositBasicHistory() {
        return $this->bank->depositBasicHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/withdrawal",
     *     tags={"BANK-API"},
     *     summary="Withdrawal Basic History (Superior Coin)",
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
     *     @SWG\Response(response=200, description="Successfully loaded withdrawal basic history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function withdrawalBasicHistory() {
        return $this->bank->withdrawalBasicHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/coin-ledger",
     *     tags={"BANK-API"},
     *     summary="Coin Ledger Bank History (Superior Coin)",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded coin ledger!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function coinLedgerHistory() {
        return $this->bank->coinLedgerHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/balances",
     *     tags={"BANK-API"},
     *     summary="View Balances Balances",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded balances!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getBalances() {
        return $this->bank->getBalances($this->request);
    }

    public function debug_getBalances() {
        return $this->bank->debugbal($this->request);
    }

    public function btcTab() {
        return $this->bank->btcTab($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/task-reward/completion",
     *     tags={"BANK-API"},
     *     summary="View Task Completion Reward History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded task completion reward history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskCompletionRewardHistory() {
        return $this->bank->taskCompletionRewardHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/task-reward/revoke",
     *     tags={"BANK-API"},
     *     summary="View Task Revoke Reward History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded task revoke reward history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskRevokeRewardHistory() {
        return $this->bank->taskRevokeRewardHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/task-reward/withdrawal",
     *     tags={"BANK-API"},
     *     summary="View Task Withdrawal History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded task withdrawal history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function taskWithdrawalHistory() {
        return $this->bank->taskWithdrawalHistory($this->request);
    }


     /**
     * @SWG\POST(
     *     path="/api/bank/history/referral/task-points/direct",
     *     tags={"BANK-API"},
     *     summary="View Direct Referral Task Points History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded direct referral task points history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function directReferralPointsHistory() {
        return $this->bank->directReferralPointsHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/referral/task-points/second",
     *     tags={"BANK-API"},
     *     summary="View Second Referral Task Points History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded second referral task points history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function secondReferralPointsHistory() {
        return $this->bank->secondReferralPointsHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/referral/task-points/third",
     *     tags={"BANK-API"},
     *     summary="View Third Referral Task Points History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded third referral task points history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function thirdReferralPointsHistory() {
        return $this->bank->thirdReferralPointsHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/gift-coin/deposit",
     *     tags={"BANK-API"},
     *     summary="View Gift Coin Deposit History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded gift coin deposit history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function gitfCoinDepositHistory() {
        return $this->bank->gitfCoinDepositHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/gift-coin/withdrawal",
     *     tags={"BANK-API"},
     *     summary="View Gift Coin Withdrawal History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *    @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded gift coin withdrawal history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function giftCoinWithdrawalHistory() {
        return $this->bank->giftCoinWithdrawalHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/referral/referral/signup-reward",
     *     tags={"BANK-API"},
     *     summary="View Referral Default Signup Reward History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded referral default signup reward history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referralDefaultSignupRewardHistory() {
        return $this->bank->referralDefaultSignupRewardHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/referral/referral/social-connect-reward",
     *     tags={"BANK-API"},
     *     summary="View Referral Social Connected Reward History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded referral social connected reward history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referralSocialConnectRewardHistory() {
        return $this->bank->referralSocialConnectRewardHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/bonus-coins/social-connect",
     *     tags={"BANK-API"},
     *     summary="View Social Connect Deposit History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded social connect deposit history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function socialConnectDepositHistory() {
        return $this->bank->socialConnectDepositHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/bonus-coins/monthly-bonus-coins",
     *     tags={"BANK-API"},
     *     summary="View Monthly Bonus Coins Deposit History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bonus coins deposit history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function bonusCoinsDepositHistory() {
        return $this->bank->bonusCoinsDepositHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/history/option-trade/deposit",
     *     tags={"BANK-API"},
     *     summary="View Option Trade Deposit History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded option trade deposit history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function optionTradeDepositHistory() {
        return $this->bank->optionTradeDepositHistory($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/history/option-trade/withdrawal",
     *     tags={"BANK-API"},
     *     summary="View Option Trade Withdrawal History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded option trade withdrawal history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function optionTradeWithdrawalHistory() {
        return $this->bank->optionTradeWithdrawalHistory($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/btc/deposit",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Deposit History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bitcoin deposit history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcDeposit() {
        return $this->bank->btcDeposit($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/btc/withdrawal",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Withdrawal History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bitcoin withdrawal history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcWithdrawal() {
        return $this->bank->btcWithdrawal($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/bank/basic-ledger/btc/option-trade",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Option Trade History",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bitcoin option trade history!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcOptionTrade() {
        return $this->bank->btcOptionTrade($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/btc-info",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Info",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded bitcoin information!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function bitcoinInfo() {
        return $this->bank->bitcoinInfo($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/bank/btc-resync",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Resync",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully resynced bitcoin wallet!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcResync() {
        return $this->bank->btcResync($this->request);
    }

            /**
     * @SWG\POST(
     *     path="/api/bank/btc-create-wallet",
     *     tags={"BANK-API"},
     *     summary="Bitcoin Create Wallet",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully created bitcoin wallet!"),
     *     @SWG\Response(response=401, description="Failed to create bitcoin wallet!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function btcCreateWallet() {
        return $this->bank->btcCreateWallet($this->request);
    }

    public function blogPayoutDepositHistory(){
        return $this->bank->blogPayoutDepositHistory($this->request);
    }

    public function membershipEarningsHistory(){
        return $this->bank->membershipEarningsHistory($this->request);
    }
}
