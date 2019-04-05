<?php

namespace App\Repository\Bank;


use BlockIo;
use App\User;
use Carbon\Carbon;
use Monero\Wallet;
use App\Model\Bank;
use App\Model\Bonus;
use App\Model\Balance;
use App\Model\RecTxid;
use App\Model\Settings;
use App\Model\TaskUser;
use App\Model\BtcWallet;
use App\Traits\BankTrait;
use App\Traits\TaskTrait;
use App\Model\OptionTrade;
use App\Model\DbWithdrawal;
use App\Traits\WalletTrait;
use App\Model\BtcWithdrawal;
use App\Model\SocialConnect;
use App\Traits\UtilityTrait;
use App\Model\ReferralReward;
use App\Model\EmailWithdrawal;
use App\Helpers\ResponseMapper;
use App\Model\BlogUserActivity;
use App\Model\BonusTransactions;
use App\Traits\Manager\UserTrait;
use App\Model\GiftCoinTransaction;
use App\ReferralMembershipEarning;
use App\Repository\UtilRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Contracts\Bank\BankInterface;
use App\Model\BankTransactionHistory;
use Illuminate\Support\Facades\Validator;

class BankRepository implements BankInterface
{
    use WalletTrait, UtilityTrait, BankTrait, UserTrait, TaskTrait;

    private $withdrawal_fee = 3;
    private $btc_less = 0.0005;
    private $default_gift_coin_trans_type = 'Kryptonia';
    protected $query;
    protected $limit = 10;

    public function index() {
        // TODO: Implement index() method.
        $user = Auth::user();
        $holdings = (new WalletRepository())->getHoldings($user->id, true);
        $payments = static::makeWallet($user->id);
        $received = null;
        if( isset($payments->payments) )
            $received = $payments->payments;

        $deposit_details = RecTxid::where('user_id', $user->id)->groupBy('txid')->get();
        $db_dep_details = [];
        if ($deposit_details) {
            foreach ($deposit_details as $deposit_detail) {
                $db_dep_details[$deposit_detail->txid] = $deposit_detail->date;
            }
        }

        $data = $received;
        $data_container = [];
        if($data){
            foreach($data as $datum) {
                if (array_key_exists($datum->tx_hash, $db_dep_details)) {
                    $selected = [
                        'tx_hash' => $datum->tx_hash,
                        'created_at' => $db_dep_details[$datum->tx_hash],
                        'amount' => ($datum->amount),
                        'block_height' => $datum->block_height,
                        'payment_id' => $datum->payment_id,
                    ];
                    $data_container[] = $selected;
                }
            }
        }
        if(count($data_container) > 0)
            return static::response('', static::responseJwtEncoder($data_container), 200, 'success');
        return static::response('', null, 201, 'success');
    }

    #TRANSACTIONAL START
    public function withdraw($request)
    {
        $user_id = Auth::id();
        $user = User::find($user_id);

        $max_amount_withdrawal = Settings::where('key', 'withdrawal_limit_amount_per_day')->first();
        $max_amount_withdrawal = $max_amount_withdrawal->value;

        $user_account_duration = Settings::where('key', 'user_account_duration_allow_withdrawal')->first();
        $user_account_duration = $user_account_duration->value;

        if ($user->ban > 0) {
            return static::response('Sender is banned', null, 403, 'failed');
        }

        if (!$request->has('rec_address')) {
            return static::response('Recipient Address is required', null, 412, 'failed');
        }
        $rec_address = $request->rec_address;
        $payment_id = $request->has('payment_id') ? $request->payment_id : '';
        $coins = $request->has('coins') ? $request->coins : 0;
        $description = $request->has('description') ? $request->description : '';

        # Check if Address is Blocked
        $block_check = $this->check_address_if_blocked($user_id, $rec_address, $payment_id);
        if ($block_check != 'passed') {
            return static::response($block_check, null, 403, 'failed');
        }

        $duration = Carbon::now()->subMinutes(60)->toDateTimeString();
        $last_withdrawals = DbWithdrawal::where('user_id', $user_id)->get();
        foreach ($last_withdrawals as $last) {
            if ($last->status != 3) {
                if ($last->status != 9) {
                    if ($last->status != 7) {
                        if ($last->status != 14 AND $duration < $last->updated_at) {
                            if ($last->status != 17 AND $duration < $last->updated_at) {
                                return static::response('Transaction Error! Code: ' . $last->status, null, 400, 'failed');
                            }
                        }
                    }
                }
            }
        }

        # THIS IS IMPORTANT START
        $WalletRepository = new WalletRepository();
        $holdings = $WalletRepository->getHoldings($user_id, true);
        # THIS IS IMPORTANT END


        if ($user->verified == 0) {
            return static::response('Unverified Email Address', null, 400, 'failed');
        }
        if ($user->status == 0) {
            return static::response('User Not Active', null, 400, 'failed');
        }
        if ($user->agreed == 0) {
            return static::response('User Not Active/Agreed', null, 400, 'failed');
        }

        $payment_id_length = strlen($payment_id);
        if ($payment_id != '') {
            if ($payment_id_length == 16 OR $payment_id_length == 64) {
                // continue
            } else {
                return static::response('Invalid Payment ID', null, 400, 'failed');
            }
        }
        $rec_address_length = strlen($rec_address);
        if ($rec_address_length == 95 OR $rec_address_length == 106) {
            // continue
        } else {
            return static::response('Invalid Receiver Address', null, 400, 'failed');
        }

        if ($coins < 1) {
            return static::response('Minimum of 1 coin to be withdraw', null, 400, 'failed');
        }

        $sup_amount = $coins + $this->withdrawal_fee;
        if ($sup_amount > $holdings['available']) {
            return static::response('Not enough amount of Superiorcoin', null, 400, 'failed');
        }

        $reg_date = new Carbon($user->created_at);
        $now = Carbon::now();
        $diff = $reg_date->diffInDays($now);
        $total_withdrawal = 0;
        $in_statuses = array('0','1','2','3','10');
        if($diff < $user_account_duration){
            return static::response('User account is less than '.$user_account_duration.' days! Not allowed to withdraw.', null, 400, 'failed');
        }else{
            $checkwithdrawalPerDay = DbWithdrawal::where('user_id', '=', $user->id)->whereIn('status',$in_statuses)->whereDate('created_at',$now->toDateString())->sum('balance');
            $total_withdrawal = $checkwithdrawalPerDay + $sup_amount;
            $checkPending = DbWithdrawal::where('user_id','=',$user->id)->where('status', DBWithdrawal::FOR_APPROVAL_STATUS)->count();
            if($checkPending > 0){
                return static::response('Not allowed to withdraw! You have pending withdrawal/s', null, 400, 'failed');
            }else{
                if($total_withdrawal > $max_amount_withdrawal){
                    return static::response('Maximum '.$max_amount_withdrawal. ' coins withdraw per day!', null, 400, 'failed');
                }
            }
        }

        $latest_withdrawal = DbWithdrawal::where('user_id', $user_id)
                            ->where('address', $user->address)
                            ->where('sendaddress', $user->sendaddress)
                            ->where('recaddress', $rec_address)
                            ->orderBy('updated_at', 'DESC')
                            ->first();
        if ($latest_withdrawal != null) {
            if ($latest_withdrawal->status == 1) {
                return static::response('Please wait for the last withdrawal to completed', null, 400, 'failed');
            } elseif ($latest_withdrawal->status == 0) {
                return static::response('Please check your email for authorizing your withdrawal', null, 400, 'failed');
            } else {
                $data = [
                    'user' => $user,
                    'coins' => $coins,
                    'rec_address' => $rec_address,
                    'payment_id' => $payment_id,
                    'description' => $description,
                    'withdrawal_fee' => $this->withdrawal_fee,
                ];
                $response = $this->process_withdrawal($data);
                return static::response($response['msg'], null, $response['code'], $response['status']);
            }
        } else {
            $data = [
                'user' => $user,
                'coins' => $coins,
                'rec_address' => $rec_address,
                'payment_id' => $payment_id,
                'description' => $description,
                'withdrawal_fee' => $this->withdrawal_fee,
            ];
            $response = $this->process_withdrawal($data);
            return static::response($response['msg'], null, $response['code'], $response['status']);
        }
    }

    public function confirm_withdrawal($key)
    {
        $email_withdrawal = EmailWithdrawal::where('key', $key)->where('status', 0)->first();
        if ($email_withdrawal == null) {
            return error(400, 'Withdrawal Key not found');
        }

        $withdrawal = DbWithdrawal::find($email_withdrawal->transid);
        if ($withdrawal == null) {
            return error(400, 'Withdrawal not found');
        }

        if ($withdrawal->status != 0) {
            if ($withdrawal->status != 3) {
                if ($withdrawal->status != 9) {
                    return error(400, 'Transaction Error! Code: ' . $withdrawal->status);
                }
            }
        }

        $WalletRepository = new WalletRepository();
        $holdings = $WalletRepository->getHoldings($withdrawal->user_id, true);

        if (($withdrawal->balance + $this->withdrawal_fee) > $holdings['available']) {
            return error(400, 'Insufficient Available Balance');
        }

        $withdrawal->status = DbWithdrawal::FOR_APPROVAL_STATUS;
        $withdrawal->save();
        $email_withdrawal->status = 1;
        $email_withdrawal->save();

        return error(200, 'Withdrawal Sending');
    }

    public function cancel_withdraw($request)
    {
        if (!$request->has('withdrawal_id')) {
            return static::response('Invalid Withdrawal Requirement', null, 412, 'failed');
        }

        $withdrawal = DbWithdrawal::find($request->withdrawal_id);
        if ($withdrawal == null) {
            return static::response('Withdrawal not found', null, 400, 'failed');
        }

        if ($withdrawal->status != 0) {
            return static::response('Failed to cancel withdrawal', null, 400, 'failed');
        }

        $withdrawal->status = 9;
        $withdrawal->save();
        $email_withdrawal = EmailWithdrawal::where('transid', $withdrawal->id)->first();
        $email_withdrawal->save();

        return static::response('Successfully cancelled withdrawal', null, 200, 'success');
    }



    public function resync($request)
    {
        $user_id = Auth::id();

        (new WalletRepository)->getHoldings($user_id, true); 
        $balance = Balance::where('user_id', $user_id)->first();
        $bank = Bank::where('user_id', $user_id)->first();
        if ($balance != null) {
            $data = [
                'total' => $balance->total,
                'available' => $balance->available,
                'on_hold' => $balance->hold,
                'pending_receive' => $balance->pending,
                'premined_coins' => $balance->premine,
                'bonus_coins' => $balance->bonus,
                'lastpen' => $balance->lastpen,
                'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s',$balance->updated_at)->toDateTimeString(),
                'address' => $bank->address,
            ];
            return static::response('Successfully Resync', static::responseJwtEncoder($data), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    public function request_resync($req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'signature' => 'required',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }

        $invalid_signature = true;
        foreach (config('signatures') as $signature) {
            if (Hash::check($signature, $req->signature)) {
                $invalid_signature = false;
                break;
            }
        }
        if ($invalid_signature) {
            return res('Your are not allowed here', null, 401);
        }

        $user = User::where('email', $req->email)->where('verified', 1)->where('status', 1)->where('agreed', 1)->where('ban', 0)->first();
        if ($user === null) {
            return res('Your are not allowed here', null, 401);
        }

        if (!Hash::check($req->password, $user->password)) {
            return res('Invalid password', null, 401);
        }

        $user_id = $user->id;
        $owner = 'Your';
        if ($req->has('user_id')) {
            if ($user->type === 9) {
                if ($user_id !== (int)$req->user_id) {
                    $owner = 'Other';
                }
                $user_id = $req->user_id;
            }
        }

        $holdings = (new WalletRepository)->getHoldings($user_id, true);
        return res($owner . ' balance is successfully resynced', $holdings);
    }
    #TRANSACTIONAL END


    function bitcoinWithdraw($request){
        $user_id = Auth::id();
        $wallet_id = $request->wallet_id;
        $amount = $request->amount;
        $address = $request->address;
        $memo = $request->has('memo') ? $request->memo : '';

        $max_amount_withdrawal = Settings::where('key', 'withdrawal_limit_amount_per_day')->first();
        $max_amount_withdrawal = $max_amount_withdrawal->value;

        $user_account_duration = Settings::where('key', 'user_account_duration_allow_withdrawal')->first();
        $user_account_duration = $user_account_duration->value;

        $btclast = BtcWithdrawal::where('user_id', '=', $user_id)->where('status','=','0')->orderBy('id', 'desc')->first();
        if($btclast){
            return static::response('Double spend please wait!', null, 400, 'failed');
        }

        if($amount < 0.1){
            return static::response('Minimum of 0.1 BTC to be withdraw!', null, 400, 'failed');
        }

        $wallet = BtcWallet::find($wallet_id);
        $btc = 0;
        if ($wallet) {
            $WalletRepository = new WalletRepository();
            $btcholdings = $WalletRepository->getBTCHoldings();
            $btc = $btcholdings['total'];
        }

        $btc_amount = $amount - $this->btc_less;

        $user = User::find($user_id);
        $reg_date = new Carbon($user->created_at);
        $now = Carbon::now();
        $diff = $reg_date->diffInDays($now);
        $total_withdrawal = 0;
        $in_statuses = array('0','10');

        if($diff < $user_account_duration){
            return static::response('User account is less than '.$user_account_duration.' days! Not allowed to withdraw.', null, 400, 'failed');
        }else{
            $checkwithdrawalPerDay = BtcWithdrawal::where('user_id', '=', $user->id)->whereIn('status',$in_statuses)->whereDate('created_at',$now->toDateString())->sum('btc');
            $total_withdrawal = $checkwithdrawalPerDay + $btc_amount;
            $checkPending = BtcWithdrawal::where('user_id','=',$user->id)->where('status', BtcWithdrawal::FOR_APPROVAL_STATUS)->count();
            if($checkPending > 0){
                return static::response('Not allowed to withdraw! You have pending withdrawal/s!', null, 400, 'failed');
            }else{
                if($total_withdrawal > $max_amount_withdrawal){
                    return static::response('Maximum '.$max_amount_withdrawal. ' coins withdraw per day!', null, 400, 'failed');
                }
            }
        }

        if($btc >= $amount && $btc > $this->btc_less) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            $btcwithdraw = new BtcWithdrawal();
            $btcwithdraw->user_id = $user->id;
            $btcwithdraw->btc = $btc_amount;
            $btcwithdraw->address = $wallet_id;
            $btcwithdraw->ip = $ip;
            $btcwithdraw->recaddress = $address;
            $btcwithdraw->memo = $memo;
            $btcwithdraw->status = BtcWithdrawal::FOR_APPROVAL_STATUS;
            if($btcwithdraw->save()){
                return static::response('Bitcoin Transfer Pending.', null, 200, 'success');
            }
        }
        return static::response('Not enough amount of Bitcoin.', null, 400, 'failed');
    }

     /**
     * @param $request [user_id][offset]
     *
     * @return \Illuminate\Http\JsonResponse
    */
    public function depositBasicHistory($request){
        $user_id = Auth::id();
        $offset =  $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";;
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $deposit_details_query = RecTxid::where('user_id', $user_id)->groupBy('txid');

        if($filter_date <> ''){
            $deposit_details_query = $deposit_details_query->whereDate('date','=', $filter_date);
        }

        if($search_key <> ''){
            $deposit_details_query = $deposit_details_query->where(function($q) use ($search_key){
                $q->where('height', '=', $search_key)
                 ->orWhere('txid', '=', $search_key);
            });
        }

        $count = $deposit_details_query->count();
        $deposit_details = $deposit_details_query->orderByDesc('date')->skip($offset)->take($limit)->get();

        $data_container = [];
        if($deposit_details){
            foreach($deposit_details as $datum) {
                $selected = [
                    'block' => $datum->height,
                    'amount' => ($datum->coins),
                    'tx_id' => $datum->txid,
                    'date_received' => $datum->date,
                    'status' => $datum->status()
                ];
                array_push($data_container,$selected);
            }
        }

        if(count($data_container) > 0){

            $list['list'] = $data_container;
            $list['count'] = $count;

            return static::response('', static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 400, 'error');
    }

    /**
     * @param $request [user_id][offset]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawalBasicHistory($request){
        $user_id = Auth::id();
        $offset =  $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";;
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }

        $dbwithdrawals_query = DbWithdrawal::where('user_id',$user_id)
                                            ->where('ban',0);

        if($filter_date <> ''){
            $dbwithdrawals_query = $dbwithdrawals_query->whereDate('created_at','=', $filter_date);
        }

        if($search_key <> ''){
            $dbwithdrawals_query = $dbwithdrawals_query->where(function($q) use ($search_key){
                  $q->where('recaddress', '=', $search_key)
                    ->orWhere('block', '=', $search_key)
                    ->orWhere('description', 'LIKE', '%'.$search_key.'%');
            });

        }

        $count = $dbwithdrawals_query->count();
        $dbwithdrawals = $dbwithdrawals_query->orderByDesc('created_at')->skip($offset)->take($limit)->get();

        $data_container = [];
        $list = [];
        if($dbwithdrawals){
            foreach($dbwithdrawals as $withdrawals){
                $data = [
                    'amount' => $withdrawals->balance,
                    'block' => $withdrawals->block,
                    'fee' => $this->withdrawal_fee,
                    'txn_date' => Carbon::createFromFormat('Y-m-d H:i:s', $withdrawals->created_at)->toDateTimeString(),
                    'description' => $withdrawals->description,
                    'status' =>  $withdrawals->status(),
                    'withdrawal_details' => $this->withdrawalDetails($withdrawals->id),
                    'rec_address' => $withdrawals->recaddress
                ];

                array_push($data_container,$data);
            }
        }

        if(count($data_container) > 0){
            $list['list'] = $data_container;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 400, 'error');
    }

     /**
     * @param int [withdrawal_id]
     *
     * @return array $data
     */
    private function withdrawalDetails($withdrawal_id){
        $data = [];
        $withdrawals = DbWithdrawal::where('id',$withdrawal_id)
                        ->orderBy('id', 'desc')
                        ->first();

        if($withdrawals){
            $user = static::get_user($withdrawals->user_id);
            $data = [
                'datetime' => Carbon::createFromFormat('Y-m-d H:i:s', $withdrawals->created_at)->toDateTimeString(),
                'sender_name' => $user->name,
                'sender_email' => $user->email,
                'amount' => $withdrawals->balance,
                'sender_address' => $withdrawals->sendaddress,
                'receiver_address' => $withdrawals->recaddress,
                'block' => $withdrawals->block,
                'payment_id' => $withdrawals->paymentid,
                'description' => $withdrawals->description,
                'status' => $withdrawals->status(),
                'ip' => $withdrawals->ip
            ];
        }
        return $data;
    }

    /**
     * @param $request [user_id][offset][filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function coinLedgerHistory($request){
        // TODO: NineCloud Copy Ledger from manager
        $user_id = Auth::id();
        $list = [];
        $data = [];
        $ending_balance = 0;

        $offset =  $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";;
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        $list_query = BankTransactionHistory::where('user_id',$user_id)
                                            ->leftJoin('bank_transactions', 'bank_transactions.id', '=', 'bank_transaction_histories.trxn_id');

        if($filter_date <> ''){
            $list_query = $list_query->whereDate('trxn_date','=',$filter_date);
        }

        if($search_key <> ''){
            $list_query = $list_query->where(function($q) use ($search_key){
                $q->where('transaction_name','LIKE','%'.$search_key.'%')
                  ->orWhere('trxn_type','LIKE','%'.$search_key.'%');
            });
        }

        $count = $list_query->count();
        $trxn_history = $list_query->orderBy('trxn_date', 'desc')
                                    ->offset($offset)
                                    ->limit($limit)
                                    ->get();


        if($trxn_history){
            foreach ($trxn_history as $key => $val){
                $item = [
                    'txn_name' => $val->transaction_name,
                    'txn_type' => $val->trxn_type,
                    'amount' => $val->amount,
                    'ending_balance' => $val->ending_balance,
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s', $val->trxn_date)->toDateTimeString(),
                ];
                array_push($data,$item);
            }
        }

        $WalletRepository = new WalletRepository();
        $holdings = $WalletRepository->getHoldings($user_id, true);
        $available = $holdings['available'];

        if(count($data) > 0){
            $list['list'] = $data;
            $list['count'] = $count;
            $list['current_ending_balance'] = $available;
            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', $available, 400, 'error');

    }

    /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function debugbal($request){
        $user_id = Auth::id();
        $balance = (new WalletRepository)->debug_getHoldings($user_id, true, true);
        dump($balance);
    }

    public function getBalances($request){
        // TODO: Ninecloud Should copy from manager
        $user_id = Auth::id();

        (new WalletRepository)->getHoldings($user_id, true);
        $data = [];
        $balance = Balance::where('user_id',$user_id)->first();
        $bank = Bank::where('user_id',$user_id)->first();

        if($balance != null){
            $data = [
                'total' => $balance->total,
                'available' => $balance->available,
                'on_hold' => $balance->hold,
                'pending_receive' => $balance->pending,
                'premined_coins' => $balance->premine,
                'bonus_coins' => $balance->bonus,
                'lastpen' => $balance->lastpen,
                'updated_at' => $balance->updated_at,
                'address' => $bank->address,
                'payment_id' => $bank->payment_id,
                'standard_address' => $bank->standard_address
            ];

            return static::response('',static::responseJwtEncoder($data), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

     public function btcTab($request) {
        // TODO: Implement btcTab() method.
        $id = $request->has('user_id') ? $request->user_id : Auth::id();
        $btc = BtcWithdrawal::where('user_id', $id)->orderBy('id', 'desc')->get();
        $btc_withdraw = OptionTrade::where('buyer_id', $id)->orderBy('id', 'desc')->get();
        $btc_deposit = OptionTrade::where('seller_id', $id)->orderBy('id', 'desc')->get();

        $btc_arr = [];
        $btc_with_arr = [];
        $btc_dep_arr = [];

        ( new ResponseMapper($btc) )->mapper(function($item, $key) use (& $btc_arr) {
            $btc_arr[$key]['user_id'] = $item['user_id'];
            $btc_arr[$key]['btc'] = $item['btc'];
            $btc_arr[$key]['address'] = $item['address'];
            $btc_arr[$key]['recaddress'] = $item['recaddress'];
            $btc_arr[$key]['status'] = $item['status'];
            $btc_arr[$key]['txid'] = $item['txid'];
        });

        ( new ResponseMapper($btc_withdraw) )->mapper(function($item, $key) use (& $btc_with_arr) {
            $btc_with_arr[$key]['coin'] = $item['coin'];
            $btc_with_arr[$key]['price'] = $item['price'];
            $btc_with_arr[$key]['total'] = $item['total'];
            $btc_with_arr[$key]['status'] = $item['status'];
        });

        ( new ResponseMapper($btc_deposit) )->mapper(function($item, $key) use (& $btc_dep_arr) {
            $btc_dep_arr[$key]['coin'] = $item['coin'];
            $btc_dep_arr[$key]['price'] = $item['price'];
            $btc_dep_arr[$key]['total'] = $item['total'];
            $btc_dep_arr[$key]['status'] = $item['status'];
        });

        return static::response('', static::responseJwtEncoder( ['btc' => $btc_arr, 'btc_withdraw' => $btc_with_arr, 'btc_deposit' => $btc_dep_arr] ), 200, 'success');
    }

    /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskCompletionRewardHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $search_key =  $request->has('search_key') ? $request->search_key : '';
        $filter_date = $request->has('filter_date') ? Carbon::parse($request->filter_date)->toDateString() : "";
        $data = [];
        $list = [];

        $history_query = DB::table('task_transaction_histories as a')
                        ->join('tasks as b', 'b.id', '=', 'a.task_id')
                        ->leftJoin('users as uc', 'uc.id', '=', 'b.user_id')
                        ->join('task_user as u',function($join){
                            $join->on('u.user_id','=','a.user_id');
                            $join->on('u.task_id','=','a.task_id');
                        })
                        ->select([
                            'a.id as history_id',
                            'a.user_id as dep_id',
                            'u.created_at as completed_dt',
                            'a.task_id',
                            'b.title',
                            'b.slug',
                            'b.reward',
                            'b.category',
                            'b.user_id as creator_id',
                            'uc.name as creator_name',
                            'uc.username as creator_username'
                        ])
                        ->where('transaction_type','=','completion')
                        ->where('a.user_id', $user_id);



        if($filter_date <> ''){
            $history_query = $history_query->whereDate('u.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                $q->where('b.title','LIKE','%'.$search_key.'%')
                                                  ->orWhere('uc.name','LIKE','%'.$search_key.'%')
                                                  ->orWhere('b.category','LIKE','%'.$search_key.'%');
                                            });
        }

        $count = $history_query->count();

        $history = $history_query->orderBy('u.created_at','desc')
                                ->offset($offset)
                                ->limit($limit)
                                ->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'category' => $value->category,
                    'task_id' => $value->task_id,
                    'task_title' => $value->title,
                    'slug' => $value->slug,
                    'reward' => $value->reward,
                    'creator_id' => $value->creator_id,
                    'creator' => $value->creator_name,
                    'creator_username' => $value->creator_username,
                    'completed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $value->completed_dt)->toDateTimeString(),
                    'status' => static::getTaskCompletionStatus($value->task_id,$value->dep_id)
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskRevokeRewardHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $filter_date = $request->has('filter_date') ? Carbon::parse($request->filter_date)->toDateString() : "";

        $data = [];
        $list = [];

        $history_query = DB::table('task_transaction_histories as a')
                        ->join('tasks as b', 'b.id', '=', 'a.task_id')
                        ->leftJoin('users as uc', 'uc.id', '=', 'b.user_id')
                        ->leftJoin('users as ucc', 'ucc.id', '=', 'a.user_id')
                        ->join('task_user as u',function($join){
                            $join->on('u.user_id','=','a.user_id');
                            $join->on('u.task_id','=','a.task_id');
                        })
                        ->leftjoin('banned_user_task as c',function($join){
                            $join->on('u.user_id','=','c.user_id');
                            $join->on('u.task_id','=','c.task_id');
                        })
                        ->select([
                            'b.user_id as task_user_id',
                            'a.id as history_id',
                            'a.user_id as dep_id',
                            'u.created_at as completed_dt',
                            'c.created_at as revoked_dt',
                            'a.task_id',
                            'a.user_id',
                            'b.title',
                            'b.slug',
                            'b.reward',
                            'b.category',
                            'b.user_id as creator_id',
                            'uc.name as creator_name',
                            'uc.username as creator_username',
                            'ucc.name as completer_name',
                            'ucc.username as completer_username'
                        ])
                        ->where('transaction_type', 'revoked')
                        ->where('a.user_id', $user_id);


        if($filter_date <> '' ){
            $history_query = $history_query->whereDate('c.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                $q->where('b.title','LIKE','%'.$search_key.'%')
                                                  ->orWhere('uc.name','LIKE','%'.$search_key.'%')
                                                  ->orWhere('ucc.name','LIKE','%'.$search_key.'%')
                                                  ->orWhere('b.category','LIKE','%'.$search_key.'%');
                                            });
        }

        $count = $history_query->count();

        $history = $history_query->orderBy('c.created_at','desc')
                                ->offset($offset)
                                ->limit($limit)
                                ->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'creator_id' => $value->creator_id,
                    'creator' => $value->creator_name,
                    'creator_username' => $value->creator_username,
                    'category' => $value->category,
                    'task_title' => $value->title,
                    'slug' => $value->slug,
                    'reward' => $value->reward,
                    'revoked_date' => Carbon::createFromFormat('Y-m-d H:i:s', $value->revoked_dt)->toDateTimeString(),
                    'completer_id' => $value->dep_id,
                    'completer' => $value->completer_name,
                    'completer_username' => $value->completer_username,
                    'type' => static::getRevokeRewardType($value->task_id,$value->task_user_id,$value->dep_id),
                    'status' => "Complete"
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }


    /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskWithdrawalHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit =  $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";

        $data = [];
        $list = [];

        $history_query = DB::table('task_user as a')
                        ->leftjoin('users as b', 'b.id', '=', 'a.user_id')
                        ->leftjoin('tasks as c', 'c.id', '=', 'a.tasK_id')
                        ->select([
                            'b.name AS completer',
                            'b.username AS completer_username',
                            'a.created_at as completed_date',
                            'a.user_id',
                            'a.task_id',
                            'c.category',
                            'c.title',
                            'c.slug',
                            'c.reward',
                            'c.expired_date',
                            'c.user_id as creator_id'
                        ])
                        ->where('a.task_creator', $user_id)
                        ->where('a.revoke', 0);



        if($filter_date <> ''){
            $history_query = $history_query->whereDate('a.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                    $q->where('c.category','LIKE','%'.$search_key.'%')
                                                      ->orWhere('c.title','LIKE','%'.$search_key.'%')
                                                      ->orWhere('b.name','LIKE','%'.$search_key.'%');
                                            });
        }
        $count = $history_query->count();

        $history = $history_query->orderBy('a.created_at','desc')
                                ->offset($offset)
                                ->limit($limit)
                                ->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $creator = static::get_user($value->creator_id);
                $item = [
                    'category' => $value->category,
                    'task_title' => $value->title,
                    'task_id' => $value->task_id,
                    'slug' => $value->slug,
                    'reward' => $value->reward,
                    'task_expiration' => $value->expired_date,
                    'completer_id' => $value->user_id,
                    'completed_by' => $value->completer,
                    'completer_username' => $value->completer_username,
                    'completed_date' => $value->completed_date,
                    'status' => static::getTaskRewardWithdrawalStatus($value->task_id)
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }


    public function taskPointsReferralHistory($param = []){

        $user_id = $param['user_id'];
        $level = $param['level'];
        $filter_date = $param['filter_date'];
        $limit = $param['limit'];
        $offset = $param['offset'];
        $search_key = $param['search_key'];
        $total_count = 0;
        $data = [];

        $referrals = static::get_referrals_with_task_points($param);

        $task_user_query = TaskUser::select(['task_user.task_id','task_user.user_id', 'task_user.created_at',
                                            't.title', 't.slug', 't.reward', 'u.name', 't.category'])
                                    ->leftJoin('users as u', 'u.id', '=', 'task_user.user_id')
                                    ->leftJoin('tasks as t', 't.id', '=', 'task_user.task_id')
                                    ->whereIn('task_user.user_id',$referrals);

        if($filter_date <> ''){
            $task_user_query = $task_user_query->whereDate('task_user.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $task_user_query = $task_user_query->where(function($q) use($search_key){
                                    $q->where('u.name','LIKE','%'.$search_key.'%')
                                        ->orWhere('t.title','LIKE','%'.$search_key.'%');
                                });
        }

        $total_count = $task_user_query->count();
        $task_user = $task_user_query->orderByDesc('task_user.created_at')->skip($offset)->take($limit)->get();

        if(count($task_user) > 0){
            foreach($task_user as $key => $ref){
                $item = [
                    'category' => $ref->category,
                    'user_id' => $ref->user_id,
                    'name' => $ref->user->name,
                    'username' => $ref->user->username,
                    'task_title' => $ref->taskInfo->title,
                    'slug' => $ref->taskInfo->slug,
                    'points' => $ref->reward,
                    'completed_date' => Carbon::createFromFormat('Y-m-d H:i:s',$ref->created_at)->toDateTimeString(),
                    'status' => static::getTaskCompletionStatus($ref->task_id,$ref->user_id)
                ];

                array_push($data,$item);
            }
        }

        $list['list'] = $data;
        $list['count'] = $total_count;
        return $list;
    }

    /**
     * @param $request [user_id, limit, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function directReferralPointsHistory($request){
        $user_id = Auth::id();
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $level = 1;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";

        $param = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset,
            'level' => $level,
            'filter_date' => $filter_date,
            'search_key' => $search_key
        ];

        $history = $this->taskPointsReferralHistory($param);
        if(count($history) > 0){
            return static::response('',static::responseJwtEncoder($history), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }

    /**
     * @param $request [user_id, limit, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function secondReferralPointsHistory($request){
        $user_id = Auth::id();
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $level = 2;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";

        $param = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset,
            'level' => $level,
            'filter_date' => $filter_date,
            'search_key' => $search_key
        ];

        $history = $this->taskPointsReferralHistory($param);

        if(count($history) > 0){
            return static::response('',static::responseJwtEncoder($history), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }

    /**
     * @param $request [user_id, limit, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function thirdReferralPointsHistory($request){
        $user_id = Auth::id();
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $level = 3;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";

        $param = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset,
            'level' => $level,
            'filter_date' => $filter_date,
            'search_key' => $search_key
        ];

        $history = $this->taskPointsReferralHistory($param);

        if(count($history) > 0){
            return static::response('',static::responseJwtEncoder($history), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function gitfCoinDepositHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query = GiftCoinTransaction::select(['gift_coin_transactions.*',
                                                'u.name AS sender_name',
                                                'u.username AS sender_username',
                                                'gift_coin_transactions.created_at AS received_date'])
                                            ->leftJoin('users as u','u.id','=','gift_coin_transactions.sender_id')
                                            ->where('receiver_id',$user_id);

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('gift_coin_transactions.created_at','=',$filter_date);
        }

        if($search_key){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                    $q->where('u.name','LIKE','%'.$search_key.'%')
                                                      ->orWhere('gift_coin_transactions.memo','LIKE','%'.$search_key.'%');
                                                });
        }

        $count = $history_query->count();

        $history = $history_query->offset($offset)
                                ->limit($limit)
                                ->orderBy('gift_coin_transactions.id','desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'transaction_type' => $this->default_gift_coin_trans_type, # this is temp;
                    'amount' => $value->coin,
                    'received_date' => Carbon::createFromFormat('Y-m-d H:i:s', $value->received_date)->toDateTimeString(),
                    'memo' => $value->memo,
                    'sender_id' => $value->sender_id,
                    'sender' => $value->sender_name,
                    'sender_username' => $value->sender_username,
                    'history' => "You received coin/s from ".$value->sender_name
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function giftCoinWithdrawalHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query = GiftCoinTransaction::select(['gift_coin_transactions.*',
                                                'u.name AS receiver_name',
                                                'u.username AS receiver_username',
                                                'gift_coin_transactions.created_at AS withdrawal_date'])
                                            ->leftJoin('users as u','u.id','=','gift_coin_transactions.receiver_id')
                                            ->where('sender_id',$user_id);

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('gift_coin_transactions.created_at','=',$filter_date);
        }

        if($search_key){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                    $q->where('u.name','LIKE','%'.$search_key.'%')
                                                      ->orWhere('gift_coin_transactions.memo','LIKE','%'.$search_key.'%');
                                                });
        }
        $count = $history_query->count();

        $history = $history_query->offset($offset)
                                ->limit($limit)
                                ->orderBy('gift_coin_transactions.id','desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'transaction_type' => $this->default_gift_coin_trans_type, # this is temp;
                    'amount' => $value->coin,
                    'withdrawal_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->withdrawal_date)->toDateTimeString(),
                    'memo' => $value->memo,
                    'receiver' => $value->receiver_name,
                    'receiver_username' => $value->receiver_username,
                    'history' => "You sent coin/s to ".$value->receiver_name
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }


     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function referralDefaultSignupRewardHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query =  ReferralReward::select(['u.name','u.email','u.username','r.created_at','referral_rewards.reward','referral_rewards.referral_id'])
                                        ->leftJoin('users as u','u.id','=','referral_rewards.referral_id')
                                        ->leftjoin('referrals as r',function($join){
                                            $join->on('r.user_id','=','referral_rewards.referral_id');
                                            $join->on('r.referrer_id','=','referral_rewards.user_id');
                                        })
                                        ->where('referral_rewards.user_id', $user_id)
                                        ->where('referral_rewards.type',1);

        $count = $history_query->count();

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('r.created_at','=',$filter_date);
        }

        if($search_key){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                $q->where('u.name','LIKE','%'.$search_key.'%')
                                                  ->orWhere('u.email','LIKE','%'.$search_key.'%');
                                            });
        }

        $history = $history_query->offset($offset)
                                ->limit($limit)
                                ->orderBy('r.created_at','desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'user_id' => $value->referral_id,
                    'name' => $value->name,
                    'email_address' => $value->email,
                    'username' => $value->username,
                    'registered_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'reward' => $value->reward,
                    'status' => $value->status(false)
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function referralSocialConnectRewardHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query = ReferralReward::select(['u.name','u.username','s.account_name','s.social','s.created_at','referral_rewards.reward','referral_rewards.referral_id'])
                                        ->leftJoin('users as u','u.id','=','referral_rewards.referral_id')
                                        ->leftjoin('referrals as r',function($join){
                                            $join->on('r.user_id','=','referral_rewards.referral_id');
                                            $join->on('r.referrer_id','=','referral_rewards.user_id');
                                        })
                                        ->leftJoin('social_connects as s','s.user_id','=','referral_rewards.referral_id')
                                        ->where('referral_rewards.user_id', $user_id)
                                        ->where('referral_rewards.type',2);

        $count = $history_query->count();

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('s.created_at','=',$filter_date);
        }

        if($search_key){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                $q->where('s.account_name','LIKE','%'.$search_key.'%')
                                                  ->orWhere('s.social','LIKE','%'.$search_key.'%');
                                            });
        }

        $history = $history_query->offset($offset)
                                ->limit($limit)
                                ->orderBy('s.created_at','desc')->get();
        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'user_id' => $value->referral_id,
                    'username' => $value->username,
                    'account_name' => $value->account_name,
                    'social_media_type' => $value->social,
                    'connected' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'reward' => $value->reward,
                    'status' => $value->status(false)
                ];
                array_push($data,$item);
            }
            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function socialConnectDepositHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $social_connection_reward = (new UtilRepository())->settings('social_connection_reward')->value;

        $history_query = SocialConnect::where('user_id',$user_id);


        if($filter_date <> ''){
            $history_query = $history_query->whereDate('created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $history_query = $history_query->where(function($q) use ($search_key){
                                                $q->where('social','LIKE','%'.$search_key.'%')
                                                  ->orWhere('account_name','LIKE','%'.$search_key.'%');
                                            });
        }

        $count = $history_query->count();

        $history = $history_query->offset($offset)->limit($limit)->orderBy('created_at','desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'social_media_type' => $value->social,
                    'account_username' => $value->account_name,
                    'connected_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'reward' => $social_connection_reward,
                    'status' => $value->status(false)
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;
            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bonusCoinsDepositHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $data = []; $list = [];

        $bonus_query = BonusTransactions::where('user_id',$user_id);

        if($filter_date <> ''){
            $bonus_query = $bonus_query->whereDate('created_at','=',$filter_date);
        }

        $count = $bonus_query->count();

        $bonus = $bonus_query->orderByDesc('created_at')->skip($offset)->take($limit)->get();

        if(count($bonus) > 0){
            foreach($bonus as $key => $value){
                $year = Carbon::parse($value->created_at)->year;
                $month = (new Bonus())->get_month_coin_desc($value->month_coin);
                $item = [
                    'month' => $value->month,
                    'year' => $value->year,
                    'amount' => (int) $value->coins,
                    'received_date' => Carbon::parse($value->created_at)->toDateTimeString()
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionTradeDepositHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query = OptionTrade::where('seller_id', $user_id);

        $count = $history_query->count();

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('created_at','=',$filter_date);
        }

        $history = $history_query->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                $item = [
                    'sup_amount' => $value->coin,
                    'btc_price' => $value->price,
                    'btc_total' => $value->total,
                    'txn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'status' => OptionTrade::deposit_default_status
                ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionTradeWithdrawalHistory($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $history_query = OptionTrade::where('buyer_id', $user_id);

        $count = $history_query->count();

        if($filter_date <> ''){
            $history_query = $history_query->whereDate('created_at','=',$filter_date);
        }


        $history = $history_query->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();

        if(count($history) > 0){
            foreach($history as $key => $value){
                    $item = [
                        'sup_amount' => $value->coin,
                        'btc_price' => $value->price,
                        'btc_total' => $value->total,
                        'txn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                        'status' => OptionTrade::deposit_default_status
                    ];
                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }


     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function btcDeposit($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $deposit_query = OptionTrade::select(['option_trades.*','bw.address'])
                                    ->leftJoin('btc_wallets as bw','bw.user_id','=','option_trades.buyer_id')
                                    ->where('seller_id', $user_id);



        if($filter_date <> ''){
            $deposit_query = $deposit_query->whereDate('created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $deposit_query = $deposit_query->where(function($q) use ($search_key){
                                                $q->where('bw.address','LIKE','%'.$search_key.'%')
                                                  ->orWhere('option_trades.sell_option_id','LIKE','%'.$search_key.'%');
                                            });
        }

        $count = $deposit_query->count();
        $deposit = $deposit_query->offset($offset)->limit($limit)->orderByDesc('id')->get();

        if(count($deposit) > 0){
            ( new ResponseMapper($deposit) )->mapper(function($item, $key) use (& $data, $user_id) {
                $data[$key]['tx_date'] = Carbon::createFromFormat('Y-m-d H:i:s',$item['created_at'])->toDateTimeString();
                $data[$key]['tx_id'] = $item['sell_option_id'];
                $data[$key]['btc_sender_address'] = $item['address'];
                $data[$key]['amount'] = $item['total'];
                $data[$key]['status'] = OptionTrade::deposit_default_status;

            });
            $list['list'] = $data;
            $list['count'] = $count;
            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'error');
    }

     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function btcWithdrawal($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $withdrawal_query = BtcWithdrawal::where('user_id', $user_id);



        if($filter_date <> ''){
            $withdrawal_query = $withdrawal_query->whereDate('created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $withdrawal_query = $withdrawal_query->where(function($q) use ($search_key){
                                                $q->where('recaddress','LIKE','%'.$search_key.'%')
                                                  ->orWhere('txid','LIKE','%'.$search_key.'%');
                                            });
        }

        $count = $withdrawal_query->count();
        $withdrawal = $withdrawal_query->skip($offset)->take($limit)->orderByDesc('id')->get();


        if(count($withdrawal) > 0){
            foreach($withdrawal as $key => $value){
                $item = [
                    'amount' => $value->btc,
                    'btc_receiver_address' => $value->recaddress,
                    'tx_id' => $value->txid,
                    'memo' => $value->memo,
                    'withdrawal_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'status' => $value->status()
                ];

                array_push($data,$item);
            }

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'error');
    }


     /**
     * @param $request [user_id, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function btcOptionTrade($request){
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $data = [];
        $list = [];

        $option_trade_query = OptionTrade::where(function($q) use ($user_id) {
                                        $q->where('seller_id',$user_id)
                                          ->orWhere('buyer_id',$user_id);
                                    });

        if($filter_date <> ''){
            $option_trade_query = $option_trade_query->whereDate('created_at','=',$filter_date);
        }

        $count = $option_trade_query->count();
        $option_trade = $option_trade_query->offset($offset)->limit($limit)->orderByDesc('id')->get();

        if(count($option_trade) > 0){
            ( new ResponseMapper($option_trade) )->mapper(function($item, $key) use (& $data, $user_id) {
                $data[$key]['tx_date'] = Carbon::createFromFormat('Y-m-d H:i:s',$item['created_at'])->toDateTimeString();
                $data[$key]['traded_for'] = "SUP";
                $data[$key]['amount'] = $item['coin'];
                $data[$key]['price'] = $item['price'];
                $data[$key]['value'] = $item['total'];
                $data[$key]['status'] = OptionTrade::deposit_default_status;

            });

            $list['list'] = $data;
            $list['count'] = $count;

            return static::response('',static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    public function bitcoinInfo($request){
        $user_id = Auth::id();

        $btcInfo = BtcWallet::where('user_id',$user_id)->first();

        $data = [];

        if($btcInfo <> null){
            $WalletRepository = new WalletRepository();
            $btcholdings = $WalletRepository->getBTCHoldings($user_id);

            $data = [
                'address' => $btcInfo->address,
                'label' => $btcInfo->label,
                'wallet_balance' => $btcholdings['total'],
                'sending' => $btcholdings['sending'],
                'receiving' => $btcholdings['receiving']
            ];

            return static::response('',static::responseJwtEncoder($data), 200, 'success');
        }

        return static::response('No Data Fetched', null, 201, 'success');
    }

    public function btcResync($request){
        $user_id = Auth::id();

        $wallet = BtcWallet::where('user_id',$user_id)->first();
        if ($wallet) {
            $WalletRepository = new WalletRepository();
            $btcholdings = $WalletRepository->getBTCHoldings($user_id);
            $data = [
                'total' => $btcholdings['total'],
                'receiving' => $btcholdings['receiving'],
                'sending' => $btcholdings['sending']
            ];
            return static::response('Successfully Resync', static::responseJwtEncoder($data), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');

    }

    public function btcCreateWallet($request){

        $block_io = new BlockIo(env('BLOCKIO_APIKEY'), env('BLOCKIO_PIN'), env('BLOCKIO_VERSION'));
        $user_id = Auth::id();
        $wallet_label = $request->wallet_label;

        $user = User::find($user_id);

        if($wallet_label == ''){
            return static::response('Wallet Label Field Required!', null, 201, 'success');
        }

        if($wallet_label == $user->email){
            $wallet = BtcWallet::where('label', '=', $wallet_label)->get();
            if ($wallet->isEmpty()) {
                try {
                    $newAddress = $block_io->get_address_by_label(array('label' => $wallet_label.'temp'));
                    $addressBalance = $block_io->get_address_balance(array('addresses' => $newAddress->data->address));
                    $wallet = new BtcWallet;
                    $wallet->user_id = $user_id;
                    $wallet->label = $wallet_label;
                    $wallet->address = $newAddress->data->address;
                    $wallet->balance = floatval($addressBalance->data->available_balance);
                    $wallet->pending_balance = floatval($addressBalance->data->pending_received_balance);
                    $wallet->save();
                    return static::response('Wallet Created Successfully', null, 200, 'success');
                } catch (\Exception $e) {

                }

                try {
                    $newAddress = $block_io->get_new_address(array('label' => $wallet_label.'temp'));
                    $addressBalance = $block_io->get_address_balance(array('addresses' => $newAddress->data->address));

                    if ($newAddress->status == true) {
                        $wallet = new BtcWallet;
                        $wallet->user_id = $user_id;
                        $wallet->label = $wallet_label;
                        $wallet->address = $newAddress->data->address;
                        $wallet->balance = floatval($addressBalance->data->available_balance);
                        $wallet->pending_balance = floatval($addressBalance->data->pending_received_balance);

                        $wallet->save();
                    } else {
                        return static::response('Error while creating address!', null, 201, 'error');
                    }

                } catch (\Exception $e) {

                    return static::response('Failed to create address', $e, 201, 'error');
                }

            }else{
                return static::response('Wallet Exist In Database', null, 201, 'error');
            }

            return static::response('Wallet Created Successfully!', null, 200, 'success');
        }

        return static::response('Failed to create address!', null, 201, 'error');
    }

    public function blogPayoutDepositHistory($request){

        $user_id = Auth::id();
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $filter_date = "";;
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        $list = [];

        $blog_deposit_query = \DB::table('blog_user_activities as bua')
                            ->leftJoin('blog_user_activity_details AS buad', 'bua.id','=', 'buad.user_activity_id')
                            ->select(['bua.created_at AS claimed_date',
                                    'buad.user_activity_details',
                                    'bua.blog_id'
                            ])
                            ->where('bua.user_id','=',$user_id)
                            ->where('bua.action','=','claim')
                            ->where('buad.user_activity_details','<>','');

        if($filter_date <> ''){
            $blog_deposit_query = $blog_deposit_query->whereDate('bua.created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $blog_deposit_query = $blog_deposit_query->where(function($q) use ($search_key){
                $q->where('buad.user_activity_details','LIKE','%'.$search_key.'%');
            });
        }
        $count =  $blog_deposit_query->count();

        $blog_deposit = $blog_deposit_query->orderByDesc('bua.created_at')->skip($offset)->take($limit)->get();

        $blog_dep_arr = [];
        if(count($blog_deposit) > 0){
            foreach ($blog_deposit as $key => $value) {
                $user_activity_details = json_decode($value->user_activity_details);
                $blogger = User::where('username',$user_activity_details->blog_payout_owner_username)->first();
                $blog_tag = \DB::table('blog_tags')->select(['tag_name'])->where('blog_id',$value->blog_id)->orderBy('id','asc')->first();
                    $item = [
                        'trans_type' => 'Upvoted Blog',
                        'blog_title' => $user_activity_details->blog_payout_title,
                        'blogger' => $blogger->name,
                        'sup' => $user_activity_details->blog_payout_reward,
                        'claimed_date' => $value->claimed_date,
                        'username' => $user_activity_details->blog_payout_owner_username,
                        'metadata' => $user_activity_details->blog_payout_meta_data,
                        'blog_tag' => $blog_tag->tag_name
                    ];

                $blog_dep_arr[] = $item;
            }

            $list['list'] = $blog_dep_arr;
            $list['count'] = $count;

            return static::response(null,static::responseJwtEncoder($list),200, 'success');
        }
        return static::response('No data fetched!', null, 400, 'error');
    }

    public function membershipEarningsHistory($request){
        $user_id = Auth::id();
        $search_key =  $request->has('search_key') ? $request->search_key : "";
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;

        $member_earnings = ReferralMembershipEarning::where('user_id',$user_id)->orderByDesc('created_at')->skip($offset)->take($limit)->get();
        $member_earnings_count = ReferralMembershipEarning::where('user_id',$user_id)->count();
        $level = "";
        $data = []; $list = [];
        if(count($member_earnings) > 0){
            foreach($member_earnings as $key => $earnings){
                if($earnings->level == 1){
                    $level = '1st';
                }else if($earnings->level == 2){
                    $level = '2nd';
                }else if($earnings->level == 3){
                    $level = '3rd';
                }

                $item = [
                    'level' => $level,
                    'referral_name' => $earnings->referral->name,
                    'referral_username' => $earnings->referral->username,
                    'membership_type' => $earnings->transaction->role->slug,
                    'purchase_date' => Carbon::parse($earnings->transaction->created_at)->toDateTimeString(),
                    'earnings' => $earnings->earnings,
                    'status' => $earnings->transaction->status()
                ];

                $data[] = $item;
            }

            $list['count'] = $member_earnings_count;
            $list['list'] = $data;

            return static::response(null,static::responseJwtEncoder($list),200, 'success');
        }

        return static::response('No data fetched!', null, 400, 'error');
    }
}   