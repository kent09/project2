<?php

namespace App\Repository\Membership;

use App\User;
use Carbon\Carbon;
use App\Model\Role;
use PayPal\Api\Item;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use App\Model\Referral;
use PayPal\Api\Payment;
use PayPal\Api\ItemList;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use App\Traits\UtilityTrait;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use App\Traits\Manager\UserTrait;
use App\ReferralMembershipEarning;
use LaravelHashids\Facades\Hashids;
use App\Model\MembershipTransaction;
use Illuminate\Support\Facades\Auth;
use PayPal\Auth\OAuthTokenCredential;
use App\Model\MembershipVoucherHistory;
use Illuminate\Support\Facades\Validator;
use App\Model\UserReputationActivityScore;
use App\Contracts\Membership\MembershipInterface;

class MembershipRepository implements MembershipInterface
{
    protected $_api_context;
    protected $currency = 'USD';
    protected $discount = [
        'percentage' => 35,
        'until' => '2019-01-13'
    ];
    protected $founder_slots = 200;

    use UserTrait, UtilityTrait;

    public function __construct()
    {
        $this->_api_context = new ApiContext(new OAuthTokenCredential(config('paypal.client_id'), config('paypal.secret')));
        $this->_api_context->setConfig(config('paypal.settings'));
    }

    public function list_roles($req)
    {
        $roles = Role::where('status', 1)->where('slug', '<>', 'admin')->get();

        return res('Success', $roles);
    }

    public function apply($req)
    {
        $validator = Validator::make($req->all(), [
            'role' => 'required',
            'quantity' => 'required',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }

        return $this->processApplication($req->role, $req->quantity);
    }

    public function application_status($req)
    {
        $validator = Validator::make($req->all(), [
            'payment_id' => 'required',
            'token' => 'required',
            'payer_id' => 'required',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }

        $payment = Payment::get($req->payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($req->payer_id);

        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() === 'approved') {
            $mt = MembershipTransaction::where('payment_id', $req->payment_id)->first();
            if ($mt !== null) {
                $mt->token = $req->token;
                $mt->payer_id = $req->payer_id;
                $mt->status = 1;
                $mt->code = Hashids::encode(Carbon::now()->timestamp);
                
                if ($mt->save()) {
                    $this->process_history($mt);
                }
            }
            return res('Payment Success', $mt);
        }
        $mt = MembershipTransaction::where('payment_id', $req->payment_id)->first();
        if ($mt !== null) {
            $mt->token = $req->token;
            $mt->payer_id = $req->payer_id;
            $mt->status = 2;
            $mt->save();
        }
        return res('Payment Failed', $mt, 400);
    }

    public function use_code($req)
    {
        $validator = Validator::make($req->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }
        
        $user_id = Auth::id();
        $mt = MembershipTransaction::where('code', $req->code)->first();
        if ($mt === null) {
            return res('Invalid Code', null, 400);
        }

        $mvh = MembershipVoucherHistory::where('trans_id', $mt->id)->first();
        if ($mvh === null) {
            return res('No footprint', null, 400);
        }
        if ($mvh->payer->type <= 1) { // Bronze - Silver
            if ($mvh->payer_id !== $user_id) {
                return res('The buyer of this membership is not allowed to gift membership', null, 400);
            }
        }

        $mt->user_id = $user_id;
        $mt->activated_at = Carbon::now()->toDateTimeString();
        $mt->code = '';
        
        if ($mt->save()) {
            $this->process_history($mt, true);
        }

        // Get Referrer
        // First Level
        $referral_1 = Referral::where('user_id', $user_id)->first();
        if ($referral_1 !== null) {
            $limitation_info = limitation_info('membership-referrer-earned', $referral_1->referrer_id);
            if ($limitation_info['value'] !== null) {
                if ($limitation_info['type'] == 'percentage') {
                    $multiplier = $limitation_info['value'] / 100;
                    $earnings = ($mt->quantity * $mt->amount) * $multiplier;
                } else {
                    $earnings = $limitation_info['value'];
                }
                $rme = new ReferralMembershipEarning;
                $rme->user_id = $referral_1->referrer_id;
                $rme->referral_id = $user_id;
                $rme->transaction_id = $mt->id;
                $rme->earnings = $earnings;
                $rme->level = 1;
                $rme->save();

                // Second Level
                $referral_2 = Referral::where('user_id', $referral_1->referrer_id)->first();
                if ($referral_2 !== null) {
                    $limitation_info = limitation_info('membership-referrer-earned', $referral_2->referrer_id);
                    if ($limitation_info['value'] !== null) {
                        if ($limitation_info['type'] == 'percentage') {
                            $multiplier = $limitation_info['value'] / 100;
                            $earnings = ($mt->quantity * $mt->amount) * $multiplier;
                        } else {
                            $earnings = $limitation_info['value'];
                        }
                        $rme = new ReferralMembershipEarning;
                        $rme->user_id = $referral_2->referrer_id;
                        $rme->referral_id = $referral_1->referrer_id;
                        $rme->transaction_id = $mt->id;
                        $rme->earnings = $earnings * .50;
                        $rme->level = 2;
                        $rme->save();

                        // Third Level
                        $referral_3 = Referral::where('user_id', $referral_2->referrer_id)->first();
                        if ($referral_3 !== null) {
                            $limitation_info = limitation_info('membership-referrer-earned', $referral_3->referrer_id);
                            if ($limitation_info['value'] !== null) {
                                if ($limitation_info['type'] == 'percentage') {
                                    $multiplier = $limitation_info['value'] / 100;
                                    $earnings = ($mt->quantity * $mt->amount) * $multiplier;
                                } else {
                                    $earnings = $limitation_info['value'];
                                }
                                $rme = new ReferralMembershipEarning;
                                $rme->user_id = $referral_3->referrer_id;
                                $rme->referral_id = $referral_2->referrer_id;
                                $rme->transaction_id = $mt->id;
                                $rme->earnings = $earnings * .25;
                                $rme->level = 3;
                                $rme->save();
                            }
                        }
                    }
                }
            }
        }

        $passes = is_limitation_passed('reputation-renewal', $user_id);
        if ($passes['passed']) {
            if ($passes['data'] !== null && $passes['data']->value === 1) {
                $uras = UserReputationActivityScore::where('user_id', $user_id)->first();
                if ($uras !== null) {
                    $uras->reputation = 100;
                    $uras->save();
                }
            }
        }

        $user = User::find($user_id);
        if ($user !== null) {
            $role = Role::find($mt->role_id);
            if ($role !== null) {
                $user->type = $role->user_type;
                $user->save();

                $memberships = MembershipTransaction::where('user_id', $user->id)->where('role_id', '<>', $mt->role_id)->where('is_expired', 0)->get();
                if (count($memberships) > 0) {
                    foreach ($memberships as $membership) {
                        $old_mt = MembershipTransaction::find($membership->id);
                        if ($old_mt !== null) {
                            $old_mt->status = 4;
                            $old_mt->is_expired = 1;
                            $old_mt->expired_at = Carbon::now()->toDateTimeString();
                            $old_mt->save();
                        }
                    }
                }
            }
        }

        return res('Successfully Activated');
    }

    public function check_code($req)
    {
        $validator = Validator::make($req->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }
        
        $user_id = Auth::id();
        $mvh = MembershipVoucherHistory::where('code', $req->code)->first();
        if ($mvh === null) {
            return res('Invalid Code', null, 400);
        }
        $info = [
            'code' => $mvh->code,
            'role' => [
                'name' => $mvh->transaction->role_info['name'],
                'slug' => $mvh->transaction->role_info['slug'],
            ],
            'duration' => [
                'value' => $mvh->transaction->quantity,
                'unit' => $mvh->transaction->unit,
            ],
            'status' => $mvh->status_info,
            'payer' => $mvh->payer_info,
            'user' => $mvh->user_info,
        ];

        return res('Valid Code', $info);
    }

    public function get_user_limitations($req)
    {
        $limitations = static::userAccessLimitations();
        return static::response(null, static::responseJwtEncoder($limitations), 200, 'success');
    }

    private function processApplication($role_slug, $quantity)
    {
        $role = Role::where('slug', $role_slug)->first();
        if ($role === null) {
            return res('Role not found', null, 400);
        }

        if ($role_slug === 'founder') {
            $founder_count = MembershipTransaction::where('role_id', $role->id)->where('is_expired', 0)->count();
            if ($founder_count >= $this->founder_slots) {
                return res('Unable to avail. ' . $role->name . ' membership is limited to ' . $this->founder_slots . ' slots only', null, 400);
            }
        }

        $item_price = $role->price->amount;
        $item_amount = $quantity * $role->price->amount;
        if (Carbon::now()->timestamp <= Carbon::createFromFormat('Y-m-d', $this->discount['until'])->timestamp) {
            $total = $item_amount;
            $discount = $total * ($this->discount['percentage'] / 100);
            $item_amount = $total - $discount;

            $price = $item_price;
            $price_discount = $price * ($this->discount['percentage'] / 100);
            $item_price = $price - $price_discount;
        }

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName($role->name)->setCurrency($this->currency)->setQuantity($quantity)->setPrice($item_price);

        $item_list = new ItemList();
        $item_list->setItems([$item]);
        
        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($item_amount);

        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($item_list)->setDescription('Kryptonia Membership Subscription Plan');

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(config('paypal.return_url'))->setCancelUrl(config('paypal.cancel_url'));

        $payment = new Payment();
        $payment->setIntent('Sale')->setPayer($payer)->setRedirectUrls($redirect_urls)->setTransactions([$transaction]);

        // dd($payment->create($this->_api_context));
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            return res('Exception:', $ex, 400);
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() === 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        if (!isset($redirect_url)) {
            return res('Unsuccessful', null, 400);
        }

        $mt = new MembershipTransaction;
        $mt->user_id = 0;
        $mt->role_id = $role->id;
        $mt->quantity = $quantity;
        $mt->amount = $item_amount / $quantity;
        $mt->unit = $role->price->unit;
        $mt->payment_id = $payment->getId();
        
        if ($mt->save()) {
            $this->process_history($mt);
        }

        return res('success', [
            'paypal_payment_id' => $payment->getId(),
            'redirect_url' => $redirect_url,
        ]);
    }

    private function process_history(MembershipTransaction $mt, bool $for_use = false): void
    {
        if ($for_use) {
            $mvh = MembershipVoucherHistory::where('trans_id', $mt->id)->first();
            if ($mvh !== null) {
                $mvh->user_id = $mt->user_id;
                $mvh->status = 2;
                $mvh->save();
            }
        } else {
            $mvh = MembershipVoucherHistory::where('trans_id', $mt->id)->first();
            if ($mvh === null) {
                $mvh = new MembershipVoucherHistory;
                $mvh->payer_id = Auth::id();
                $mvh->user_id = 0;
                $mvh->trans_id = $mt->id;
                $mvh->code = null; // To be fill after successful payment
                $mvh->status = 0; // Pending
                $mvh->save();
            } else {
                if ($mt->status === 1) {
                    $mvh->code = $mt->code;
                    $mvh->status = 1; // Usable
                    $mvh->save();
                }
            }
        }
    }
}
