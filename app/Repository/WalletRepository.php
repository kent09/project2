<?php

namespace App\Repository;

use App\User;
use App\CoinSales;
use Carbon\Carbon;
use Monero\Wallet;
use App\Model\Bank;
use App\Model\Fund;
use App\Model\Sale;
use App\Model\Task;
use App\Model\Bonus;
use App\Model\Balance;
use App\Model\Premine;
use App\Model\RecTxid;
use App\Model\Release;
use App\Model\Referral;
use App\Model\TaskUser;
use App\Model\BtcWallet;
use App\Model\TaskWizard;
use App\Model\OptionTrade;
use App\ReferralTaskPoint;
use App\Model\DbWithdrawal;
use App\Model\UserFreeTask;
use App\Traits\WalletTrait;
use App\Model\BtcWithdrawal;
use App\Model\SocialConnect;
use App\Model\LockedScOption;
use App\Model\ReferralReward;
use Monero\Wallet as MWallet;
use App\Model\EmailWithdrawal;
use App\Model\BonusTransactions;
use App\Model\GiftCoinTransaction;
use App\Model\KryptoniaBlogReward;
use Illuminate\Support\Facades\Auth;

class WalletRepository
{
    use WalletTrait;


    public static function getHoldings($user_id, $use_api = false, $update_db_balance = true){
        $holdings = [
            'total' => 0,
            'hold' => 0,
            'available' => 0,
            'lastpen' => 0,
            'pending' => 0,
            'bonus' => 0,
            'premine' => 0,
        ];

        $holdings = json_encode($holdings);
        $holdings = json_decode($holdings);

        # SET USER ID VALUE START
        $user = User::find($user_id);
        if ($user === null) {
            return $holdings;
        }
        # SET USER ID VALUE END

        # GETTING USER BANK DATA FROM DB START
        $bank = Bank::where("user_id", $user_id)->first();
        # GETTING USER BANK DATA FROM DB END

        # CREATE BANK FOR USER THAT NO BANK YET START
        if (!$bank) {
            $bank = static::makeBank($user_id);
        }
        # CREATE BANK FOR USER THAT NO BANK YET END

        # INITIATING CONNECTION TO DAEMON START
        $wallet = new Wallet(config('app.wallet.ip'));
        $wallet2 = new Wallet(config('app.wallet.ip'), '8082');
        # INITIATING CONNECTION TO DAEMON END

        # RUN API CALL FUNCTION TO CHECK FROM BLOCKCHAIN FOR ACCURATE DATA FOR TRANSACTION START
        if ($use_api) {
            # GETTING THE BLOCK HEIGHT FROM THE DAEMON START
            $height = $wallet->getHeight();
            $height = json_decode($height);
            $height = $height->height;
            $lastpen = 0;
            # GETTING THE BLOCK HEIGHT FROM THE DAEMON END

            $checklast = DbWithdrawal::where('user_id', $user_id)->orderBy('id', 'desc')->first();
            if ($checklast) {
                $lastpen = static::checklast($checklast, $lastpen, $height, $user_id);
            }
        }
        # RUN API CALL FUNCTION TO CHECK FROM BLOCKCHAIN FOR ACCURATE DATA FOR TRANSACTION END

        # GETTING AMOUNT FROM DAEMON START
        $amount = static::getbal($user_id);
        $pending = $amount['pending'];
        $funds = Fund::where('email', $user->email)->first();
        $hold = 0;
        $balance = 0;

        $bonus_on_hold = settings('bonus_on_hold_duration')->value;

        if ($funds !== null) {
            # CALCULATING HOLD FOR HAVING FUND RECORD START
            $btcpay = CoinSales::where('email', '=', $user->email)->first();
            $totalBalance = static::check_coin_balance($user, $funds->balance);
            $locked = LockedScOption::where('user_id', $user_id)->first();
            if ($locked !== null) {
                $totalBalance = $totalBalance + $locked->coins;
            }
            $release = Release::orderBy('id', 'desc')->first();
            $premine = Premine::where('user_id', $user_id)->first();
            $bonus = Bonus::where('user_id', $user_id)->first();
            if ($premine) {
                $totalBalance += $premine->balance;
            }

            $hold = $totalBalance - $release->percent * $totalBalance;
            if ($bonus !== null) {
                if (isset($bonus->fb_coin)) {
                    $hold -= $bonus->fb_coin / 3;
                }
                if ($bonus->updated_at >= Carbon::now()->subDays($bonus_on_hold)->toDateTimeString()) {
                    $hold += $bonus->coins;
                }
            }
            # CALCULATING HOLD FOR HAVING FUND RECORD END
        } else {
            # CALCULATING HOLD FOR NOT HAVING FUND RECORD START
            $btcpay = CoinSales::where('email', $user->email)->first();
            $totalBalance = static::check_coin_balance($user, 0);
            $locked = LockedScOption::where('user_id', $user_id)->first();
            if ($locked !== null) {
                $totalBalance = $totalBalance + $locked->coins;
            } else {
                $totalBalance = 0;
            }
            $release = Release::orderBy('id', 'desc')->first();
            $premine = Premine::where('user_id', '=', $user_id)->first();
            $bonus = Bonus::where('user_id', '=', $user_id)->first();
            if ($premine) {
                $totalBalance += $premine->balance;
            }

            $hold = $totalBalance - $release->percent * $totalBalance;
            if ($bonus) {
                if ($bonus->updated_at >= Carbon::now()->subDays($bonus_on_hold)->toDateTimeString()) {
                    $hold += $bonus->coins;
                }
            }
            # CALCULATING HOLD FOR HAVING FUND RECORD END
        }

        $locked = LockedScOption::where('user_id', $user_id)->first();
        $bank->balance = $amount['notsold'];
        $balance = $bank->balance;
        if ($locked !== null) {
            $balance = $bank->balance + $locked->coins;
        }
        $bank->save();
        # GETTING AMOUNT FROM DAEMON END

        # ADD TO BALANCE FROM BASIC DEPOSIT START
        /*$deposits = RecTxid::where('user_id', $user_id)->groupBy('txid')->get(['coins']);
        if (count($deposits) > 0) {
            foreach ($deposits as $deposit) {
                $balance += $deposit->coins;
            }
        }*/
        # ADD TO BALANCE FROM BASIC DEPOSIT END

        # SUB FROM BALANCE FORM BASIC WITHDRAWAL START
        $withdrawals = DBWithdrawal::where('user_id')->where('status', '>=', 0)->where('status', '<=', 3)->sum('balance');
        $balance -= $withdrawals;
        # SUB FROM BALANCE FORM BASIC WITHDRAWAL END

        # ADD TO BALANCE FROM TASK REWARD FOR TASK COMPLETING START
        $tasks = Task::where('user_id', $user_id)->where('status', 1)->get(['expired_date', 'final_cost', 'fee_charge', 'is_free_task']);
        if (count($tasks) > 0) {
            foreach ($tasks as $task) {
                if ($task->is_free_task !== 1) {
                    if ($task->expired_date >= Carbon::now()) {
                        $hold += $task->final_cost + $task->fee_charge;
                    }
                }
            }
        }
        $taskrewards = TaskUser::where('user_id', $user_id)->where('revoke', 0)->get(['created_at', 'reward']);
        if (count($taskrewards) > 0) {
            foreach ($taskrewards as $reward) {
                $holding_days = 14;
                if (settings('bank_on_hold_duration') !== null) {
                    $holding_days = (int)settings('bank_on_hold_duration')->value;
                }
                $limitation = limitation_info('hold-day', $user_id);
                if ($limitation['value'] !== null) {
                    $holding_days = $limitation['value'];
                }
                if ($reward->created_at >= Carbon::now()->subDays($holding_days)) {
                    $hold += $reward->reward;
                    $balance += $reward->reward;
                } else {
                    $balance += $reward->reward;
                }
            }
        }
        # ADD TO BALANCE FROM TASK REWARD FOR TASK COMPLETING END

        # SUB FROM BALANCE TO TASK COST FOR CREATING TASK START
        $taskcost_amount = TaskUser::with('task')->whereHas('task', function ($task) {
            $task->where('is_free_task', 0);
        })->where('task_creator', $user_id)->where('revoke', 0)->sum('reward');
        $balance -= $taskcost_amount;
        $bonus = 0;
        # SUB FROM BALANCE TO TASK COST FOR CREATING TASK END

        # SUB FROM BALANCE TO SENDING GIFT START
        $gift_coins_amount = GiftCoinTransaction::where('sender_id', $user_id)->where('active', 1)->sum('coin');
        $balance -= $gift_coins_amount;
        # SUB FROM BALANCE TO SENDING GIFT END

        # ADD TO BALANCE FROM RECEIVING GIFT START
        $gift_coins_amount = GiftCoinTransaction::where('receiver_id', $user_id)->where('active', 1)->sum('coin');
        $balance += $gift_coins_amount;
        # ADD TO BALANCE FROM RECEIVING GIFT END

        # GET PREMINED AND BONUS TO ADD IN BALANCE START
        $premine = Premine::where('user_id', $user_id)->first();
        $bonus = Bonus::where('user_id', $user_id)->first();
        $premine_1 = 0;
        if ($premine !== null) {
            $balance += $premine->balance;
            $premine_1 = $premine->balance;
        }
        $bonus_1 = 0;
        if ($bonus !== null) {
            $balance += $bonus->coins;
            $bonus_1 = $bonus->coins;
        }
        # GET PREMINED AND BONUS TO ADD IN BALANCE END

        # ADD TO BALANCE FROM CONNECTING SOCIAL ACCOUNTS START
        $socials = SocialConnect::where('user_id', $user_id)->get(['created_at', 'version', 'status']);
        if (count($socials) > 0) {
            foreach ($socials as $social) {
                $now = Carbon::now();
                $created = Carbon::createFromFormat('Y-m-d H:i:s', $social->created_at);
                $days = $now->diffInDays($created);
                if ($social->version == 1) { // SET ALL TO BALANCE FOR OLD USERS
                    $balance += 100;
                } elseif ($social->version == 2) { // FILTER NEW USERS ACCORDING TO DAYS FROM CONNECTION\
                    $on_hold = settings('bank_on_hold_duration')->value;
                    if ($days > $on_hold) {
                        $balance += 100;
                    } else {
                        if ($social->status == 1) {
                            $hold += 100;
                            $balance += 100;
                        }
                    }
                }
            }
        }
        # ADD TO BALANCE FROM CONNECTING SOCIAL ACCOUNTS END

        # ADD TO BALANCE FROM TASK WIZARD START
        $task_wizard = TaskWizard::where('user_id', $user_id)->first();
        if ($task_wizard !== null) {
            if ($task_wizard->status === 2 or $task_wizard->status === 1) {
                //task completed
                $balance += $task_wizard->task_reward;
            } elseif ($task_wizard->status === 3) {
                //task revoked
                $hold += $task_wizard->task_reward;
                $balance += $task_wizard->task_reward;
            }
        }
        # ADD TO BALANCE FROM TASK WIZARD END

        # ADD TO BALANCE FROM REFERRAL REWARD START
        $referral_rewards = ReferralReward::where('user_id', $user_id)->get();
        if (count($referral_rewards) > 0) {
            foreach ($referral_rewards as $reward) {
                if ($reward->version == 1) {
                    if ($reward->getStatusInfoAttribute()['text'] == 'paid') {
                        $balance += $reward->reward;
                    } else {
                        $hold += $reward->reward;
                        $balance += $reward->reward;
                    }
                } elseif ($reward->version == 2) {
                    if ($reward->referral) {
                        if ($reward->referral->getStatusInfoAttribute()['text'] == 'active') {
                            if ($reward->getStatusInfoAttribute()['text'] == 'paid') {
                                $balance += $reward->reward;
                            } else {
                                $hold += $reward->reward;
                                $balance += $reward->reward;
                            }
                        }
                    }
                }
            }
        }
        # ADD TO BALANCE FROM REFERRAL REWARD END

        # ADD TO BALANCE FROM REFERRAL POINT START
        // TODO: New Update from BE3
        $referral_points = Referral::where('referrer_id', $user_id)->get();
        if (count($referral_points) > 0) {
            foreach ($referral_points as $point) {
                $be_added = false;
                if ($point->version == 1) {
                    if ($point->getStatusInfoAttribute()['text'] != 'paid') {
                        $hold += $point->points;
                        $hold += $point->second_lvl_points;
                        $hold += $point->third_lvl_points;
                    }
                    $be_added = true;
                } elseif ($point->version == 2) {
                    if ($point->referral->getStatusInfoAttribute()['text'] == 'active') {
                        if ($point->getStatusInfoAttribute()['text'] != 'paid') {
                            $hold += $point->points;
                            $hold += $point->second_lvl_points;
                            $hold += $point->third_lvl_points;
                        }
                        $be_added = true;
                    }
                }
                if ($be_added) {
                    $balance += $point->points;
                    $balance += $point->second_lvl_points;
                    $balance += $point->third_lvl_points;
                }
            }
        }
        # ADD TO BALANCE FROM REFERRAL POINT END

        # ADD TO BALANCE FROM REFUNDED WITHDRAWAL (17) START
        $duration = Carbon::now()->subMinutes(60)->toDateTimeString();
        $dbwithdrawal_amount = DBWithdrawal::where('user_id', $user_id)->where('status', 17)->where('updated_at', '<', $duration)->sum('balance');
        $balance += $dbwithdrawal_amount;
        # ADD TO BALANCE FROM REFUNDED WITHDRAWAL (17) END

        # ADD BLOG PAYOUT TO BALANCE START
        $blog_reward_amount = KryptoniaBlogReward::where('user_id', $user_id)->where('status', 1)->sum('reward');
        $balance += $blog_reward_amount;
        # ADD BLOG PAYOUT TO BALANCE END

        # CONSOLIDATE ALL VALUE AND ROUND START
        $available = $balance - $hold;
        $balance = floor($balance);

        $hold = floor($hold);
        $pending = floor($pending);
        $bonus = floor($bonus_1);
        $available = floor($available);

        $holdings->total = $balance;
        $holdings->hold = $hold;
        $holdings->available = $available;
        $holdings->lastpen = $lastpen;
        $holdings->pending = $pending;
        $holdings->bonus = $bonus;
        $holdings->premine = $premine_1;
        # CONSOLIDATE ALL VALUE AND ROUND END

        if ($update_db_balance) {
            # SAVE TO BALANCE DB START
            $dbbalance = Balance::where('user_id', $user_id)->first();
            if ($dbbalance == null) {
                $dbbalance = new Balance;
                $dbbalance->user_id = $user_id;
            }
            $dbbalance->total = $holdings->total;
            $dbbalance->hold = $holdings->hold;
            $dbbalance->available = $holdings->available;
            $dbbalance->lastpen = $holdings->lastpen;
            $dbbalance->pending = $holdings->pending;
            $dbbalance->bonus = $holdings->bonus;
            $dbbalance->premine = $holdings->premine;
            $dbbalance->type = 0;
            $dbbalance->status = 1;
            $dbbalance->save();
            # SAVE TO BALANCE DB END
        }

        return (array)$holdings;
    }

    public static function debug_getHoldings($user_id, $use_api = false, $update_db_balance = true){
        $holdings = [
            'total' => 0,
            'hold' => 0,
            'available' => 0,
            'lastpen' => 0,
            'pending' => 0,
            'bonus' => 0,
            'premine' => 0,
        ];

        $holdings = json_encode($holdings);
        $holdings = json_decode($holdings);

        # SET USER ID VALUE START
        $user = User::find($user_id);
        if ($user === null) {
            return $holdings;
        }
        # SET USER ID VALUE END

        # GETTING USER BANK DATA FROM DB START
        $bank = Bank::where("user_id", $user_id)->first();
        # GETTING USER BANK DATA FROM DB END

        # CREATE BANK FOR USER THAT NO BANK YET START
        if (!$bank) {
            $bank = static::makeBank($user_id);
        }
        # CREATE BANK FOR USER THAT NO BANK YET END

        # INITIATING CONNECTION TO DAEMON START
        $wallet = new Wallet(config('app.wallet.ip'));
        $wallet2 = new Wallet(config('app.wallet.ip'), '8082');
        # INITIATING CONNECTION TO DAEMON END

        # RUN API CALL FUNCTION TO CHECK FROM BLOCKCHAIN FOR ACCURATE DATA FOR TRANSACTION START
        if ($use_api) {
            # GETTING THE BLOCK HEIGHT FROM THE DAEMON START
            $height = $wallet->getHeight();
            $height = json_decode($height);
            $height = $height->height;
            $lastpen = 0;
            # GETTING THE BLOCK HEIGHT FROM THE DAEMON END

            $checklast = DbWithdrawal::where('user_id', $user_id)->orderBy('id', 'desc')->first();
            if ($checklast) {
                $lastpen = static::checklast($checklast, $lastpen, $height, $user_id);
            }
        }
        # RUN API CALL FUNCTION TO CHECK FROM BLOCKCHAIN FOR ACCURATE DATA FOR TRANSACTION END

        # GETTING AMOUNT FROM DAEMON START
        $amount = static::getbal($user_id);
        $pending = $amount['pending'];
        $funds = Fund::where('email', $user->email)->first();
        $hold = 0;
        $balance = 0;

        $bonus_on_hold = settings('bonus_on_hold_duration')->value;

        if ($funds !== null) {
            # CALCULATING HOLD FOR HAVING FUND RECORD START
            $btcpay = CoinSales::where('email', '=', $user->email)->first();
            $totalBalance = static::check_coin_balance($user, $funds->balance);
            $locked = LockedScOption::where('user_id', $user_id)->first();
            if ($locked !== null) {
                $totalBalance = $totalBalance + $locked->coins;
            }
            $release = Release::orderBy('id', 'desc')->first();
            $premine = Premine::where('user_id', $user_id)->first();
            $bonus = Bonus::where('user_id', $user_id)->first();
            if ($premine) {
                $totalBalance += $premine->balance;
            }

            $hold = $totalBalance - $release->percent * $totalBalance;
            if ($bonus !== null) {
                if (isset($bonus->fb_coin)) {
                    $hold -= $bonus->fb_coin / 3;
                }
                if ($bonus->updated_at >= Carbon::now()->subDays($bonus_on_hold)->toDateTimeString()) {
                    $hold += $bonus->coins;
                }
            }
            # CALCULATING HOLD FOR HAVING FUND RECORD END
        } else {
            # CALCULATING HOLD FOR NOT HAVING FUND RECORD START
            $btcpay = CoinSales::where('email', $user->email)->first();
            $totalBalance = static::check_coin_balance($user, 0);
            $locked = LockedScOption::where('user_id', $user_id)->first();
            if ($locked !== null) {
                $totalBalance = $totalBalance + $locked->coins;
            } else {
                $totalBalance = 0;
            }
            $release = Release::orderBy('id', 'desc')->first();
            $premine = Premine::where('user_id', '=', $user_id)->first();
            $bonus = Bonus::where('user_id', '=', $user_id)->first();
            if ($premine) {
                $totalBalance += $premine->balance;
            }

            $hold = $totalBalance - $release->percent * $totalBalance;
            if ($bonus) {
                if ($bonus->updated_at >= Carbon::now()->subDays($bonus_on_hold)->toDateTimeString()) {
                    $hold += $bonus->coins;
                }
            }
            # CALCULATING HOLD FOR HAVING FUND RECORD END
        }

        $locked = LockedScOption::where('user_id', $user_id)->first();
        $bank->balance = $amount['notsold'];
        $balance = $bank->balance;
        if ($locked !== null) {
            $balance = $bank->balance + $locked->coins;
        }
        $bank->save();
        # GETTING AMOUNT FROM DAEMON END

        # ADD TO BALANCE FROM BASIC DEPOSIT START
        /*$deposits = RecTxid::where('user_id', $user_id)->groupBy('txid')->get(['coins']);
        if (count($deposits) > 0) {
            foreach ($deposits as $deposit) {
                $balance += $deposit->coins;
            }
        }*/
        # ADD TO BALANCE FROM BASIC DEPOSIT END
        dump($balance);
        # SUB FROM BALANCE FORM BASIC WITHDRAWAL START
        $withdrawals = DBWithdrawal::where('user_id')->where('status', '>=', 0)->where('status', '<=', 3)->sum('balance');
        $balance -= $withdrawals;
        # SUB FROM BALANCE FORM BASIC WITHDRAWAL END

        # ADD TO BALANCE FROM TASK REWARD FOR TASK COMPLETING START
        $tasks = Task::where('user_id', $user_id)->where('status', 1)->get(['expired_date', 'final_cost', 'fee_charge', 'is_free_task']);
        if (count($tasks) > 0) {
            foreach ($tasks as $task) {
                if ($task->is_free_task !== 1) {
                    if ($task->expired_date >= Carbon::now()) {
                        $hold += $task->final_cost + $task->fee_charge;
                    }
                }
            }
        }
        dump($balance);
        $taskrewards = TaskUser::where('user_id', $user_id)->where('revoke', 0)->get(['created_at', 'reward']);
        if (count($taskrewards) > 0) {
            foreach ($taskrewards as $reward) {
                $holding_days = 14;
                if (settings('bank_on_hold_duration') !== null) {
                    $holding_days = (int)settings('bank_on_hold_duration')->value;
                }
                $limitation = limitation_info('hold-day', $user_id);
                if ($limitation['value'] !== null) {
                    $holding_days = $limitation['value'];
                }
                if ($reward->created_at >= Carbon::now()->subDays($holding_days)) {
                    $hold += $reward->reward;
                    $balance += $reward->reward;
                } else {
                    $balance += $reward->reward;
                }
            }
        }
        # ADD TO BALANCE FROM TASK REWARD FOR TASK COMPLETING END
        dump($balance);
        # SUB FROM BALANCE TO TASK COST FOR CREATING TASK START
        $taskcost_amount = TaskUser::with('task')->whereHas('task', function ($task) {
            $task->where('is_free_task', 0);
        })->where('task_creator', $user_id)->where('revoke', 0)->sum('reward');
        $balance -= $taskcost_amount;
        $bonus = 0;
        # SUB FROM BALANCE TO TASK COST FOR CREATING TASK END

        # SUB FROM BALANCE TO SENDING GIFT START
        $gift_coins_amount = GiftCoinTransaction::where('sender_id', $user_id)->where('active', 1)->sum('coin');
        $balance -= $gift_coins_amount;
        # SUB FROM BALANCE TO SENDING GIFT END

        # ADD TO BALANCE FROM RECEIVING GIFT START
        $gift_coins_amount = GiftCoinTransaction::where('receiver_id', $user_id)->where('active', 1)->sum('coin');
        $balance += $gift_coins_amount;
        # ADD TO BALANCE FROM RECEIVING GIFT END

        # GET PREMINED AND BONUS TO ADD IN BALANCE START
        $premine = Premine::where('user_id', $user_id)->first();
        $bonus = Bonus::where('user_id', $user_id)->first();
        $premine_1 = 0;
        if ($premine !== null) {
            $balance += $premine->balance;
            $premine_1 = $premine->balance;
        }
        $bonus_1 = 0;
        if ($bonus !== null) {
            $balance += $bonus->coins;
            $bonus_1 = $bonus->coins;
        }
        # GET PREMINED AND BONUS TO ADD IN BALANCE END
        dump($balance);
        # ADD TO BALANCE FROM CONNECTING SOCIAL ACCOUNTS START
        $socials = SocialConnect::where('user_id', $user_id)->get(['created_at', 'version', 'status']);
        if (count($socials) > 0) {
            foreach ($socials as $social) {
                $now = Carbon::now();
                $created = Carbon::createFromFormat('Y-m-d H:i:s', $social->created_at);
                $days = $now->diffInDays($created);
                if ($social->version == 1) { // SET ALL TO BALANCE FOR OLD USERS
                    $balance += 100;
                } elseif ($social->version == 2) { // FILTER NEW USERS ACCORDING TO DAYS FROM CONNECTION\
                    $on_hold = settings('bank_on_hold_duration')->value;
                    if ($days > $on_hold) {
                        $balance += 100;
                    } else {
                        if ($social->status == 1) {
                            $hold += 100;
                            $balance += 100;
                        }
                    }
                }
            }
        }
        # ADD TO BALANCE FROM CONNECTING SOCIAL ACCOUNTS END

        # ADD TO BALANCE FROM TASK WIZARD START
        $task_wizard = TaskWizard::where('user_id', $user_id)->first();
        if ($task_wizard !== null) {
            if ($task_wizard->status === 2 or $task_wizard->status === 1) {
                //task completed
                $balance += $task_wizard->task_reward;
            } elseif ($task_wizard->status === 3) {
                //task revoked
                $hold += $task_wizard->task_reward;
                $balance += $task_wizard->task_reward;
            }
        }
        # ADD TO BALANCE FROM TASK WIZARD END
        dump("ref start",$balance);
        # ADD TO BALANCE FROM REFERRAL REWARD START
        $referral_rewards = ReferralReward::where('user_id', $user_id)->get();

        if (count($referral_rewards) > 0) {

            foreach ($referral_rewards as $reward) {
                if ($reward->version == 1) {
                    dump("reward1",$reward);
                    if ($reward->getStatusInfoAttribute()['text'] == 'paid') {
                        $balance += $reward->reward;
                    } else {
                        $hold += $reward->reward;
                        $balance += $reward->reward;
                    }

                } elseif ($reward->version == 2) {

                    if ($reward->referral !== null) {

                        if ($reward->referral->getStatusInfoAttribute()['text'] == 'active') {
                            if ($reward->getStatusInfoAttribute()['text'] == 'paid') {
                                $balance += $reward->reward;
                            } else {
                                $hold += $reward->reward;
                                $balance += $reward->reward;
                            }
                        }
                        dump("balance-ref2",$balance);
                        dump("reward2",$reward->reward);
                    }else{
                        dump("reward2 wtf",$reward->referral);
                    }

                }
            }
        }
        # ADD TO BALANCE FROM REFERRAL REWARD END
        dump("ref BE3", $balance);
        # ADD TO BALANCE FROM REFERRAL POINT START
        // TODO: New Update from BE3
        $referral_points = Referral::where('referrer_id', $user_id)->get();
        if (count($referral_points) > 0) {
            foreach ($referral_points as $point) {
                $be_added = false;
                if ($point->version == 1) {
                    if ($point->getStatusInfoAttribute()['text'] != 'paid') {
                        $hold += $point->points;
                        $hold += $point->second_lvl_points;
                        $hold += $point->third_lvl_points;
                    }
                    $be_added = true;
                } elseif ($point->version == 2) {
                    if ($point->referral->getStatusInfoAttribute()['text'] == 'active') {
                        if ($point->getStatusInfoAttribute()['text'] != 'paid') {
                            $hold += $point->points;
                            $hold += $point->second_lvl_points;
                            $hold += $point->third_lvl_points;
                        }
                        $be_added = true;
                    }
                }
                if ($be_added) {
                    $balance += $point->points;
                    $balance += $point->second_lvl_points;
                    $balance += $point->third_lvl_points;
                }
            }
        }
        # ADD TO BALANCE FROM REFERRAL POINT END
        dump("ref end", $balance);
        # ADD TO BALANCE FROM REFUNDED WITHDRAWAL (17) START
        $duration = Carbon::now()->subMinutes(60)->toDateTimeString();
        $dbwithdrawal_amount = DBWithdrawal::where('user_id', $user_id)->where('status', 17)->where('updated_at', '<', $duration)->sum('balance');
        $balance += $dbwithdrawal_amount;
        # ADD TO BALANCE FROM REFUNDED WITHDRAWAL (17) END
        dump($balance);
        # ADD BLOG PAYOUT TO BALANCE START
        $blog_reward_amount = KryptoniaBlogReward::where('user_id', $user_id)->where('status', 1)->sum('reward');
        $balance += $blog_reward_amount;
        # ADD BLOG PAYOUT TO BALANCE END
        dump($balance);
        # CONSOLIDATE ALL VALUE AND ROUND START
        $available = $balance - $hold;
        $balance = floor($balance);

        $hold = floor($hold);
        $pending = floor($pending);
        $bonus = floor($bonus_1);
        $available = floor($available);

        $holdings->total = $balance;
        $holdings->hold = $hold;
        $holdings->available = $available;
        $holdings->lastpen = $lastpen;
        $holdings->pending = $pending;
        $holdings->bonus = $bonus;
        $holdings->premine = $premine_1;
        # CONSOLIDATE ALL VALUE AND ROUND END
        dump($balance);
        if ($update_db_balance) {
            # SAVE TO BALANCE DB START
            $dbbalance = Balance::where('user_id', $user_id)->first();
            if ($dbbalance == null) {
                $dbbalance = new Balance;
                $dbbalance->user_id = $user_id;
            }
            $dbbalance->total = $holdings->total;
            $dbbalance->hold = $holdings->hold;
            $dbbalance->available = $holdings->available;
            $dbbalance->lastpen = $holdings->lastpen;
            $dbbalance->pending = $holdings->pending;
            $dbbalance->bonus = $holdings->bonus;
            $dbbalance->premine = $holdings->premine;
            $dbbalance->type = 0;
            $dbbalance->status = 1;
            $dbbalance->save();
            # SAVE TO BALANCE DB END
        }

        return (array)$holdings;
    }

    public static function checklast($checklast, $lastpen, $height, $user_id)
    {
        if ($checklast->status == 2) {
            if ($height - $checklast->block <= 10) {
                $lastpen = 1;
            } else {
                $checklast2 = EmailWithdrawal::where('user_id', $user_id)->where('transid', $checklast->id)->orderBy('id', 'desc')->first();
                if ($checklast2) {
                    if ($height - $checklast2->block <= 10) {
                        $lastpen = 1;
                    } else {
                        $txid = Txid::where('transid', $checklast->id)->where('status', 0)->get();
                        if ($txid) {
                            foreach ($txid as $hash) {
                                $stats = file_get_contents('http://superior-coin.info:8081/api/transaction/' . $hash->txids);
                                $stats = json_decode($stats);
                                $stats = $stats->data;
                                if (isset($stats->confirmations)) {
                                    $stats = $stats->confirmations;
                                } else {
                                    $lastpen = 1;
                                    return $lastpen;
                                }

                                if ($stats < 10) {
                                    $lastpen = 1;
                                    break;
                                }
                                $hash->status = 1;
                                $hash->save();
                            }
                            if ($lastpen == 0) {
                                $checklast->status = 3;
                                $checklast->save();
                            }
                        }
                    }
                } else {
                    $lastpen = 1;
                }
            }
        }
        return $lastpen;
    }

    public static function getbal($user_id)
    {
        $wallet = new Wallet(config('app.wallet.ip'));
        $wallet2 = new Wallet(config('app.wallet.ip'), '8082');
        $bank = static::makeBank($user_id);

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
        if (isset($payments->payments)) {
            foreach ($payments->payments as $payment) {
                $amount = $amount + $payment->amount;
                if ($height - $payment->block_height <= 10) {
                    $pending = $pending + $payment->amount;
                }
            }
            $received = $payments->payments;
        }
        $splitIntegrated = $wallet2->splitIntegratedAddress($bank->sendaddress);
        $splitIntegrated = json_decode($splitIntegrated);
        $payments = $wallet2->getPayments($splitIntegrated->payment_id);
        $payments = json_decode($payments);
        if (isset($payments->payments)) {
            foreach ($payments->payments as $payment) {
                $amount = $amount - $payment->amount - 300000000;
            }
        }
        $pending = $pending / 100000000;
        $amount = $amount / 100000000;
        $amount = $amount - $pending;
        $user = User::with(['bank', 'wallet', 'optionTrade', 'optionSell'])->find($user_id);
        $amount = ['notsold' => $amount - static::check_coin_balance($user), 'received' => $amount, 'pending' => $pending];
        return $amount;
    }

    public static function make($user_id)
    {
        $user = User::find($user_id);
        $bank = Bank::where('user_id', $user_id)->first();
        if ($bank !== null) {
            return $bank;
        }

        $wallet = new Wallet(config('app.wallet.ip'), config('app.wallet.port_1'));
        $wallet2 = new Wallet(config('app.wallet.ip'), config('app.wallet.port_2'));

        $order = CoinSales::where('email', $user->email)->first();
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
            $splitIntegrated = $wallet->splitIntegratedAddress($wallet1->integrated_address);
            $splitIntegrated = json_decode($splitIntegrated);
            $bank = new Bank();
            $bank->balance = 0;
            $bank->user_id = $user->id;
            $bank->pending = 0;
            $bank->address = $wallet1->integrated_address;
            $bank->sendaddress = $wallets->integrated_address;
            $bank->payment_id = $splitIntegrated->payment_id;
            $bank->standard_address = $splitIntegrated->standard_address;
            $bank->save();
            return $bank;
        }
    }
    
    public static function check_coin_balance(User $user, $amount = null)
    {
        $sellOptionBalance = 0;
        $buyOptionBalance = 0;
        if ($user->optionSell) {
            foreach ($user->optionSell as $trade) {
                if ($trade->status == 0) {
                    if (!$trade->bid_status == 0) {
                        $sellOptionBalance += $trade->coin;
                    }
                }
            }
        }
        $tradeTotalBalance = ((float)$sellOptionBalance + (float)$buyOptionBalance);
        if ($amount !== null) {
            $totalBalance = ((float)$amount - (float)$tradeTotalBalance);
        }
        return $tradeTotalBalance;
    }

    public static function getBTCHoldings($user_id=0)
    {
        if ($user_id == 0){
            $user_id = Auth::id();
        }

        $user = User::find($user_id);

        $btc = 0;
        $pendings = 0;
        $pendingr = 0;
        $wallet = null;
        $email = $user->email;
        if(BtcWallet::where('label', '=', $email)->first()) {
            $wallet = User::with('wallet')->where('id',$user_id)->first();
        }
        if ($wallet) {
            $btc = static::walletUpdateApi($wallet->id);
            if (!is_numeric($btc)){
                $btc = 0;
            }
            $sending = BtcWithdrawal::where('user_id','=',$user_id)->where('status','=',0)->get();
            if($sending){
                foreach ($sending as $send){
                    $btc = $btc - $send->btc - 0.0005;
                    $pendings = $pendings + $send->btc;
                }
            }
            $sending = BtcWithdrawal::where('user_id','=',$user_id)->where('status','=',1)->get();
            if($sending){
                foreach ($sending as $send){
                    $btc = $btc - $send->btc - 0.0005;
                }
            }
            $trades = OptionTrade::where('status', '=', 0)->where('buyer_id', '=', $user_id)->get();
            foreach ($trades as $trade) {
                if ($trade) {
                    $btc = $btc - $trade->total;
                    if($trade->created_at > Carbon::now()->subDay(1)) {
                        $pendings = $pendings + $trade->total;
                    }
                }
            }
            $trade = null;
            $trades = OptionTrade::where('status', '=', 0)->where('seller_id', '=', $user_id)->get();
            foreach ($trades as $trade) {
                if ($trade) {
                    $btc = $btc + $trade->total;
                    if($trade->created_at > Carbon::now()->subDay(1)) {
                        $pendingr = $pendingr + $trade->total;
                    }
                }
            }
        }

        $holdings = array('total' => $btc, 'receiving' => $pendingr, 'sending' => $pendings);
        return $holdings;
    }
}