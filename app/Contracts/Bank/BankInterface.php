<?php

namespace App\Contracts\Bank;


interface BankInterface
{
    public function index();

    public function withdraw($request);

    public function confirm_withdrawal($key);

    public function cancel_withdraw($request);

    public function resync($request);

    public function request_resync($request);

    public function bitcoinWithdraw($request);

    public function depositBasicHistory($request);

    public function withdrawalBasicHistory($request);

    public function coinLedgerHistory($request);

    public function getBalances($request);

    public function btcTab($request);
    
    public function taskCompletionRewardHistory($request);

    public function taskRevokeRewardHistory($request);

    public function taskWithdrawalHistory($request);

    public function taskPointsReferralHistory($request);

    public function gitfCoinDepositHistory($request);

    public function giftCoinWithdrawalHistory($request);

    public function referralDefaultSignupRewardHistory($request);

    public function referralSocialConnectRewardHistory($request);

    public function socialConnectDepositHistory($request);

    public function bonusCoinsDepositHistory($request);

    public function optionTradeDepositHistory($request);

    public function optionTradeWithdrawalHistory($request);

    public function btcDeposit($request);

    public function btcWithdrawal($request);

    public function btcOptionTrade($request);

    public function bitcoinInfo($request);

    public function btcResync($request);

    public function btcCreateWallet($request);

    public function blogPayoutDepositHistory($request);

    public function membershipEarningsHistory($request);
}
