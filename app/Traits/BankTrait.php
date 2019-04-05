<?php

namespace App\Traits;

use Monero\Wallet;
use App\Model\Bank;
use App\Model\RecTxid;
use App\Model\BtcWallet;
use App\Model\DbWithdrawal;
use App\Model\BlockedAddress;
use App\Model\BankTransaction;
use App\Model\BankTransactionHistory;
use Carbon\Carbon;
use App\Model\EmailWithdrawal;
use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Mail;
use App\Mail\WithdrawalMailer;
use App\User;

define('DEPO','deposit');
define('WDRAW','withdrawal');
define('PAGE_LIMIT',10);

trait BankTrait
{
    public function check_address_if_blocked(int $user_id, $recipient_address, $payment_id = '')
    {
        $bank = Bank::where('user_id', $user_id)->first();
        if ($bank == null) {
            return 'passed';
        }
        $blocked = BlockedAddress::where(function ($q) use ($bank, $recipient_address) {
            $q->where('address', $bank->address)->orWhere('address', $recipient_address);
        })->where('status', 1)->first();
        if ($blocked == null) {
            return 'passed';
        }
        if ($blocked->payment_id == '' OR $blocked->payment_id == null) {
            return 'blocked-address';
        } else {
            if ($blocked->payment_id == $payment_id) {
                return 'blocked-payment-id';
            } else {
                return 'blocked-address';
            }
        }
    }

    public function process_withdrawal($data)
    {
        if (gettype($data) == 'array') {
            $data = json_encode($data);
            $data = json_decode($data);
        }
        $user = $data->user;
        $email = $data->user->email;
        $user_id = $data->user->id;
        $coins = $data->coins;
        $rec_address = $data->rec_address;
        $payment_id = $data->payment_id;
        $description = $data->description;
        $withdrawal_fee = $data->withdrawal_fee;
        $bank = Bank::where('user_id', $user_id)->first();

        $host = config('app.wallet.ip');
        $port = 8082;
        $wallet1 = new Wallet(config('app.wallet.ip'));
        $wallet2 = new Wallet($host, $port);
        
        $height = $wallet1->getHeight();
        $height = json_decode($height);
        $height = $height->height;

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $latest = DbWithdrawal::where('user_id', $user_id)->where('status', '<>', 9)->orderBy('id', 'DESC')->first();
        if ($latest != null) {
            if (($height - $latest->block) <= 10) {
                $response = [
                    'msg' => 'Please wait until last withdrawal completed',
                    'code' => 400,
                    'status' => 'failed'
                ];
                return $response;
            }
        }

        $WalletRepository = new WalletRepository();
        $holdings = $WalletRepository->getHoldings($user_id, true);
        $available = $holdings['available'];

        if (($coins + $withdrawal_fee) > $available) {
            $response = [
                'msg' => 'Not enough available coins',
                'code' => 400,
                'status' => 'failed'
            ];
            return $response;
        }

        $withdrawal = new DbWithdrawal();
        $withdrawal->user_id = $user_id;
        $withdrawal->balance = $coins;
        $withdrawal->block = $height;
        $withdrawal->address = $bank->address;
        $withdrawal->sendaddress = $bank->sendaddress;
        $withdrawal->recaddress = $rec_address;
        $withdrawal->paymentid = $payment_id;
        $withdrawal->description = $description;
        $withdrawal->ip = $ip;
        $withdrawal->status = 0;
        $withdrawal->txid = 0;
        $withdrawal->type = 0;
        $withdrawal->save();

        $key = $this->generate_random_string();
        $email_withdrawal = new EmailWithdrawal();
        $email_withdrawal->key = $key;
        $email_withdrawal->email = $email;
        $email_withdrawal->status = 0;
        $email_withdrawal->transid = $withdrawal->id;
        $email_withdrawal->save();

        $user = User::find($withdrawal->user_id);
        $send_email = new WithdrawalMailer($withdrawal, $email_withdrawal, $user);
        Mail::to($email)->send($send_email);

        //         $email = new EmailVerification($user);
        // Mail::to($user->email)->send($email);

        $response = [
            'msg' => 'Please check your email for authorizing your withdrawal',
            'code' => 200,
            'data' => $email_withdrawal,
            'status' => 'success'
        ];
        return $response;
    }

    private function generate_random_string($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * save bank transaction history
     *
     * @param array $data
     * @return array
     */
    public static function save_bank_transaction_history($data = []) {
        $data = to_obj($data);

        $user_id = $data->user_id ?? Auth::id();
        $trxn_type = $data->trxn_type; 
        $trxn_date = $data->trxn_date;
        $amount = $data->amount;
        $trxn_id = $data->trxn_id;
        // $status = $data->status;
        // $tx_id = $data->tx_id;
        // $payment_id = $data->payment_id;
        // $address = $data->address;
        // $block = $data->block;
        // $description = $data->description;
        $cur_ending_balance = static::get_current_ending_balance($user_id);

        if($trxn_type == DEPO){
            $ending_balance = $cur_ending_balance + $amount;
        }else{
            $ending_balance = $cur_ending_balance - $amount;
        }
        
        $bank_history = new BankTransactionHistory();
        $bank_history->user_id = $user_id;
        $bank_history->trxn_id = $trxn_id;
        $bank_history->trxn_type = $trxn_type;
        $bank_history->trxn_date = $trxn_date;
        $bank_history->amount = $amount;
        $bank_history->ending_balance = $ending_balance;


        // if(isset($status)){
        //     $bank_history->status = $status;
        // }
        // if(isset($tx_id)){
        //     $bank_history->tx_id = $tx_id;
        // }
        // if(isset($payment_id)){
        //     $bank_history->payment_id = $payment_id;
        // }
        // if(isset($address)){
        //     $bank_history->address = $address;
        // }
        // if(isset($block)){
        //     $bank_history->block = $block;
        // }
        // if(isset($description)){
        //     $bank_history->description = $description;
        // }

        if($bank_history->save()){
            return true;
        }
        return false;

    }

     /**
     * get bank transaction history
     *
     * @param array $data ['user_id','offset','filter_date']
     * @return array
     */
    public static function get_bank_transaction_history($data = []) {
        $data = to_obj($data);
        $user_id = $data->user_id ?? Auth::id();
        $offset = $data->offset ?? 0;
        $filter_date = $data->filter_date;
        
        if($filter_date != ""){
            $filter_date = Carbon::parse($filter_date)->toDateString();

            $list = BankTransactionHistory::where('user_id',$data->user_id)    
                                          ->leftJoin('bank_transactions', 'bank_transactions.id', '=', 'bank_transaction_histories.trxn_id') 
                                          ->whereDate('trxn_date','=',$filter_date)
                                          ->orderBy('trxn_date', 'desc')
                                          ->offset($offset)
                                          ->limit(PAGE_LIMIT)
                                          ->get();
        }else{
            $list = BankTransactionHistory::where('user_id',$data->user_id)
                                          ->leftJoin('bank_transactions', 'bank_transactions.id', '=', 'bank_transaction_histories.trxn_id') 
                                          ->offset($offset)
                                          ->orderBy('trxn_date', 'desc')
                                          ->limit(PAGE_LIMIT)
                                          ->get();
        }
        
        if(count($list) > 0){
            return return_data($list);
        }
        return return_data($list,400);
    }

    
     /**
     * get current ending balance from bank history
     *
     * @param int $user_id
     * @param date $filter_date
     * @return int
     */
    public static function get_current_ending_balance($user_id, $filter_date='') {
        $user_id = $user_id ?? Auth::id();

        if($filter_date <> ''){
            $filter_date = Carbon::parse($filter_date)->toDateString();
            $balance = BankTransactionHistory::select('ending_balance')
                                          ->where('user_id',$user_id)
                                          ->whereDate('trxn_date','=',$filter_date)
                                          ->orderBy('trxn_date','desc')
                                          ->first();
        }else{
            $balance = BankTransactionHistory::select('ending_balance')
                                            ->where('user_id',$user_id)
                                            ->orderBy('trxn_date','desc')
                                            ->first();
        }
        
        
        if(count($balance) > 0){
            return $balance['ending_balance'];
        }
        
        return 0;
    }

    /* get bitcoin wallet address
    *
    * @param int $user_id
    * @return string 
    */
   public static function get_btc_wallet_address($user_id) {
       $user_id = $user_id ?? Auth::id();

       $btc_wallet = BtcWallet::where('user_id',$user_id)->first();

       if($btc_wallet <> null){
            return $btc_wallet->address;
       }       
       return "";
   }

   /* get bank transaction id by name
    *
    * @param int $user_id
    * @return string 
    */
    public static function get_bank_transaction_by_name($transaction_name) {
        $trans = BankTransaction::where('transaction_name',$transaction_name)->first();
        return $trans->id;
    }

    public function count_sup_for_approval_withdrawals()
    {
        $total = DbWithdrawal::where('status', DbWithdrawal::FOR_APPROVAL_STATUS)->count();

        return $total;
    }

}