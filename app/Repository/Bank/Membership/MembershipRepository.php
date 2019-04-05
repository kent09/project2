<?php

namespace App\Repository\Bank\Membership;

use App\User;
use Carbon\Carbon;
use App\Traits\UtilityTrait;
use App\ReferralMembershipEarning;
use App\Model\MembershipWithdrawal;
use LaravelHashids\Facades\Hashids;
use App\Model\MembershipTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendMembershipWithdrawalConfirmation;
use App\Contracts\Bank\Membership\MembershipInterface;

class MembershipRepository implements MembershipInterface
{
    use UtilityTrait;

    private $_MIN = [
        'usd' => 25,
        'btc' => 0.0071
    ];

    public function balance($req)
    {
        $balance = $this->holdings(Auth::id());

        return static::response('', static::responseJwtEncoder($balance), 200, 'success');
    }

    public function request_withdraw($req)
    {
        $validator = Validator::make($req->all(), [
            'amount' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return static::response($validator->errors(), null, 412, 'error');
        }
        $types = ['btc', 'paypal'];
        if (!in_array($req->type, $types)) {
            return static::response('Invalid withdrawal type', null, 400, 'error');
        }
        if ($req->type === 'paypal') {
            $validator = Validator::make($req->all(), [
                'paypal_email' => 'required|email',
            ]);
            if ($validator->fails()) {
                return static::response($validator->errors(), null, 412, 'error');
            }
            $currency = 'usd';
        } elseif ($req->type === 'btc') {
            $validator = Validator::make($req->all(), [
                'btc_address' => 'required',
            ]);
            if ($validator->fails()) {
                return static::response($validator->errors(), null, 412, 'error');
            }
            $currency = 'btc';
        }

        $pendings = MembershipWithdrawal::where('user_id', Auth::id())->where('status', 0)->count();
        if ($pendings > 0) {
            return static::response('You have pending transaction, please confirm from your email or cancel the transaction', null, 400, 'error');
        }
        $processings = MembershipWithdrawal::where('user_id', Auth::id())->where('status', 2)->count();
        if ($processings > 0) {
            return static::response('You have on-processed transaction, please wait or contact support', null, 400, 'error');
        }


        $balance = $this->holdings(Auth::id(), $currency);
        if ($balance['available'] < $this->_MIN[$currency]) {
            return static::response('Your available balance should be more or equal to ' . $this->_MIN[$currency]." ". strtoupper($currency), null, 400, 'error');
        }
        if ($req->amount < $this->_MIN[$currency]) {
            return static::response('Should be a minimum of ' . $this->_MIN[$currency]. strtoupper($currency) . ' can be processed', null, 400, 'error');
        }

        $withdrawal = new MembershipWithdrawal;
        $withdrawal->user_id = Auth::id();
        $withdrawal->amount = $req->amount;
        $withdrawal->type = $req->type;
        $withdrawal->paypal_email = $req->paypal_email;
        $withdrawal->btc_address = $req->btc_address;
        $withdrawal->email_token = 'WTH-' . Hashids::encode(Auth::id() + Carbon::now()->timestamp);
        $withdrawal->status = 0;
        $withdrawal->save();
        
        Mail::to(Auth::user()->email)->send(new SendMembershipWithdrawalConfirmation($withdrawal));
        
        $withdrawal->makeHidden(['email_token']);
        return static::response('Please check your email to confirm withdrawal', static::responseJwtEncoder($withdrawal), 200, 'success');
    }

    public function confirm_withdrawal($key) {
        $withdrawal = MembershipWithdrawal::where('email_token', $key)->first();
        if ($withdrawal === null) {
            return error(400, 'Invalid token');
        }
        $withdrawal->email_token = '';
        $withdrawal->status = 2;
        $withdrawal->save();

        return error(200, 'Token Confirmed, Withdrawal is on processed');
    }

    public function withdrawal_history($req)
    {
        $page = 1;
        if ($req->has('page')) {
            $page = $req->page;
        }
        $limit = 10;
        if ($req->has('limit')) {
            $limit = $req->limit;
        }
        $offset = ($page * $limit) - $limit;

        $total = MembershipWithdrawal::where('user_id', Auth::id())->count();
        $items = MembershipWithdrawal::where('user_id', Auth::id())->skip($offset)->take($limit)->orderBy('created_at', 'DESC')->get();

        return static::response('Success', 
            static::responseJwtEncoder([
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'items' => $items,
        ]), 200, 'success');
    }

    public function billing_history($req)
    {
        $page = 1;
        if ($req->has('page')) {
            $page = $req->page;
        }
        $limit = 10;
        if ($req->has('limit')) {
            $limit = $req->limit;
        }
        $offset = ($page * $limit) - $limit;

        $total = MembershipTransaction::where('user_id', Auth::id())->count();
        $items = MembershipTransaction::where('user_id', Auth::id())->skip($offset)->take($limit)->orderBy('created_at', 'DESC')->get();

        return static::response('Success', 
            static::responseJwtEncoder([
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'items' => $items,
        ]), 200, 'success');
    }

    private function holdings($user_id = 0, $currency = 'usd')
    {
        if ($user_id === 0) {
            $user_id = Auth::id();
        }
        $balance = [
            'total' => 0,
            'hold' => 0,
            'available' => 0,
            'currency' => $currency
        ];

        $user = User::find($user_id);
        if ($user !== null) {
            if ($currency === 'usd') {
                $total = ReferralMembershipEarning::where('user_id', $user->id)->sum('earnings');
                $withdrawals = MembershipWithdrawal::where('user_id', $user->id)->where('status', 3)->sum('amount');
                $total -= $withdrawals;
                $hold = MembershipWithdrawal::where('user_id', $user->id)->where(function ($q) {
                    $q->where('status', 0)->orWhere('status', 2);
                })->sum('amount');
                $available = $total - $hold;
    
                $balance = [
                    'total' => $total,
                    'hold' => $hold,
                    'available' => $available,
                    'currency' => $currency
                ];
            } elseif ($currency === 'btc') {

            }
        }

        return $balance;
    }
}
