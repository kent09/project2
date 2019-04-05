<?php

namespace App\Traits;


use App\Model\Bank;
use App\Model\Fund;
use App\Model\Sale;
use App\Model\Txid;
use App\Model\BtcWallet;
use App\Model\Withdrawal;
use App\User;
use Monero\Wallet;
use BlockIo;
use Illuminate\Support\Facades\Auth;
trait WalletTrait
{
    public static function makeBank($user_id) {
        $user = User::find($user_id);
        $bank = Bank::where('user_id', $user_id)->first();
        if ($bank !== null) {
            return $bank;
        }

        $wallet = new Wallet(config('app.wallet.ip'));
        $wallet2 = new Wallet(config('app.wallet.ip'), '8082');

        $order = Sale::where('email', $user->email)->first();
        if ($order) {
            $order = Fund::where('email', $user->email)->first();
            if (!$order) {
                // 'Account Being Funded.'
                return null;
            } else {
                $bank = new Bank();
                $bank->balance = 0;
                $bank->user_id = $user->id;
                $bank->pending = 0;
                $bank->address = $order->address;
                $bank->sendaddress = $order->sendaddress;
                $bank->save();
                return $bank;
            }
        } else {
            $wallet1 = $wallet->integratedAddress();
            $wallets = $wallet2->integratedAddress();
            $wallet1 = json_decode($wallet1);
            $wallets = json_decode($wallets);
            $bank = new Bank();
            $bank->balance = 0;
            $bank->user_id = $user->id;
            $bank->pending = 0;
            $bank->address = $wallet1->integrated_address;
            $bank->sendaddress = $wallets->integrated_address;
            $bank->save();
            return $bank;
        }
    }

    public static function checkLast($checklast, $lastpen, $height, $id) {
        if ($checklast->status == 2){
            if($height - $checklast->block <= 10)
            {
                $lastpen = 1;
            }else{
                $checklast2 = Withdrawal::where('user_id','=',$id)->where('transid','=', $checklast->id)->orderBy('id', 'desc')->first();
                if($checklast2){
                    if($height - $checklast2->block <= 10)
                    {
                        $lastpen = 1;
                    }else{
                        $txid = Txid::where('transid','=', $checklast->id)->where('status','=', 0)->get();
                        if($txid){
                            foreach ($txid as $hash){
                                $stats = file_get_contents('http://superior-coin.info:8081/api/transaction/'.$hash->txids);
                                $stats = json_decode($stats);
                                $stats = $stats->data;
                                if(isset($stats->confirmations)){
                                    $stats = $stats->confirmations;
                                }
                                else{
                                    $lastpen = 1;
                                    return $lastpen;
                                }

                                if ($stats < 10){
                                    $lastpen = 1;
                                    break;
                                }
                                $hash->status = 1;
                                $hash->save();
                            }
                            if($lastpen == 0){
                                $checklast->status = 3;
                                $checklast->save();
                            }
                        }
                    }
                }
                else{
                    $lastpen = 1;
                }
            }
        }
        return $lastpen;
    }

    public static function getBal($wallet, $wallet2, $bank, $id) {
        $splitIntegrated = $wallet->splitIntegratedAddress($bank->address);
        $splitIntegrated = json_decode($splitIntegrated);
        $payments = $wallet->getPayments($splitIntegrated->payment_id);
        $payments = json_decode($payments);
        $height = $wallet->getHeight();
        $height = json_decode($height);
        $height = $height->height;
        $pending = 0;
        $amount = 0;
        $received = null;
        if(isset($payments->payments)){
            foreach ($payments->payments as $payment){
                $amount = $amount + $payment->amount;
                if ($height - $payment->block_height <= 10){
                    $pending = $pending + $payment->amount;
                }
            }
            $received = $payments->payments;
        }
        $splitIntegrated = $wallet2->splitIntegratedAddress($bank->sendaddress);
        $splitIntegrated = json_decode($splitIntegrated);
        $payments = $wallet2->getPayments($splitIntegrated->payment_id);
        $payments = json_decode($payments);
        if(isset($payments->payments)) {
            foreach ($payments->payments as $payment) {
                $amount = $amount - $payment->amount - 300000000;
            }
        }
        $pending = $pending/100000000;
        $amount = $amount/100000000;
        $amount = $amount - $pending;
        $user = User::with(['bank', 'wallet', 'optionTrade', 'optionSell'])->find($id);
        $amount = array('notsold' => $amount - static::checkCoinBalance($user), 'received' => $amount, 'pending' => $pending);
        return $amount;
    }

    public static function checkCoinBalanceF(User $user, $amount) {
        $sellOptionBalance = 0;
        $buyOptionBalance = 0;
        if($user->optionSell) {
            foreach ($user->optionSell as $trade) {
                if($trade->status == 0) {
                    if(!$trade->bid_status == 0) {
                        $sellOptionBalance += $trade->coin;
                    }
                }
            }
        }
        $tradeTotalBalance = ((float) $sellOptionBalance + (float) $buyOptionBalance);
        $totalBalance = ((float) $amount - (float) $tradeTotalBalance);
        return $totalBalance;
    }

    public static function makeWallet(int $user_id) {
        $bank = Bank::where('user_id', $user_id)->first();

        $split_integrated = (new Wallet(config('app.wallet.ip')))->splitIntegratedAddress($bank->address);
        $split_integrated = json_decode($split_integrated);

        $payments = (new Wallet(config('app.wallet.ip')))->getPayments($split_integrated->payment_id);
        return json_decode($payments);
    }

    protected static function checkCoinBalance(User $user) {
        $sellOptionBalance = 0;
        $buyOptionBalance = 0;
        if($user->optionSell) {
            foreach ($user->optionSell as $trade) {
                if($trade->status == 0) {
                    if(!$trade->bid_status == 0) {
                        $sellOptionBalance += $trade->coin;
                    }
                }
            }
        }
        $tradeTotalBalance = ((float) $sellOptionBalance + (float) $buyOptionBalance);
        return $tradeTotalBalance;
    }

    public static function walletUpdateApi($wallet_id) {
        $balance = 0;
        $block_io = new BlockIo(config('app.blockioapikey'), config('app.blockiopin'), config('app.blockioversion'));

        if ($wallet_id == null) {
            return return_data('Wallet id not specified', 400);
        }

        if ($wallet_id == Auth::user()->wallet()->first()->id) {
            // First check to see if we have a wallet with sent id
            $wallet = BtcWallet::find($wallet_id);
            if ($wallet !== null) {
                try {
                    $addressBalance = $block_io->get_transactions(array('type' => 'received','addresses' => $wallet->address));
                    foreach ($addressBalance->data->txs as $tx){
                        $balance = $balance + $tx->amounts_received[0]->amount;
                    }
                    if(count($addressBalance->data->txs) >= 25){
                        return return_data('Contact support!', 400);
                    }

                    $wallet->balance = floatval($balance);
                    $addressBalance = $block_io->get_address_balance(array('addresses' => $wallet->address));
                    $wallet->pending_balance = $addressBalance->data->pending_received_balance;
                    $wallet->save();
                    Auth::user()->bank->bit_coins = $balance;
                    Auth::user()->bank->update();

                } catch (\Exception $e) {
                    return return_data($e. 'Unknown error occured', 400);
                }
            } else {
                return return_data('Unknown Wallet Id Specified', 400);
            }
            return $wallet->balance;
        }
        return return_data('Unknown Wallet Id Specified', 400);
    }
}