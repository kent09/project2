<?php

namespace App\Repository;

use App\Model\Bank;
use App\Model\RecTxid;
use App\Model\DbWithdrawal;
use App\Model\Settings;
use App\Model\Referral;
use App\Model\TaskUser;
use App\Model\SocialConnect;
use App\Model\LeaderBoard;
use App\Model\ReferralReward;
use App\Model\BankTransactionHistory;
use App\Model\ReferralLeaderBoardParticipant;
use App\Model\Withdrawal;
use App\Model\Txid;
use App\Model\BonusTransactions;
use App\Model\Bonus;
use App\Model\BlockedDomain;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Traits\BankTrait;
use App\Helpers\UtilHelper;
use App\Console\Commands\CalculateReferralPoints;
use Illuminate\Support\Facades\DB;
use App\Model\LeaderBoardOwn;
use App\User;
use Monero\Wallet;
use App\Repository\WalletRepository;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;

class CronRepository
{
	use BankTrait;

	public $direct_cap;
	public $second_lvl_cap;
	public $thrid_lvl_cap;

	protected $weekly_participant_list = [];
	protected $monthly_participant_list = [];
	protected $all_time_participant_list = [];

	public function __construct()
	{
		if (Schema::hasTable('settings')){
			$settings = Settings::where('key', 'referral_point_cap')->first();
			if ($settings == null) {
				$settings = new Settings;
				$settings->key = "referral_point_cap";
				$settings->value = 10;
				$settings->description = "Referral Point Cap/Limit from Direct Referral devide by half after next levels";
				$settings->save();
			}
			$this->direct_cap = $settings->value;
			$this->second_lvl_cap = $this->direct_cap / 2;
			$this->thrid_lvl_cap = $this->second_lvl_cap / 2;
		} else {
			$this->direct_cap = 10;
			$this->second_lvl_cap = 5;
			$this->thrid_lvl_cap = 2.5;
		}
	}

	public function sortTopEarnerFromReferral($class)
	{
		$class->line('Start Sorting');
		// TRUNCATING LEADERBOARD
		$participants = new ReferralLeaderBoardParticipant;
		$participants->truncate();
		
		# GET ALL UNIQUE USERS
		$unique_users = Referral::groupBy('referrer_id')->where('status', '<>', 0)->get();
		$weekly_list = [];
		$monthly_list = [];
		$all_time_list = [];
		if (count($unique_users) > 0){
			$main_bar = $class->output->createProgressBar(count($unique_users));
			$i = 1;
			foreach ($unique_users as $unique){
				# CLEARING LIST
				$this->weekly_participant_list = [];
				$this->monthly_participant_list = [];
				$this->all_time_participant_list = [];

				$referrals = Referral::where('referrer_id', $unique->referrer_id)->get();
				# GET ALL REFERRALS
				if (count($referrals) > 0){
					# FROM WEEKLY
					$this->getUserEarnings($referrals, $class, 'weekly');
					$earned = 0;
					$rewards = 0;
					$task_points = 0;
					if (count($this->weekly_participant_list) > 0){
						foreach ($this->weekly_participant_list as $item){
							$rewards += $item['rewards'];
							$task_points += $item['task_points'];
						}	
						$earned = $rewards + $task_points;					
					}
					$weekly_list[$unique->referrer_id] = [$earned, $rewards, $task_points];
					arsort($weekly_list);
					$class->info('WEEKLY of User '.$i);

					# FROM MONTHLY
					$this->getUserEarnings($referrals, $class, 'monthly');
					$earned = 0;
					$rewards = 0;
					$task_points = 0;
					if (count($this->monthly_participant_list) > 0){
						foreach ($this->monthly_participant_list as $item){
							$rewards += $item['rewards'];
							$task_points += $item['task_points'];
						}	
						$earned = $rewards + $task_points;						
					}
					$monthly_list[$unique->referrer_id] = [$earned, $rewards, $task_points];
					arsort($monthly_list);
					$class->info('MONTHLY of User '.$i);

					# FROM ALL TIME
					$this->getUserEarnings($referrals, $class);
					$earned = 0;
					$rewards = 0;
					$task_points = 0;
					if (count($this->all_time_participant_list) > 0){
						foreach ($this->all_time_participant_list as $item){
							$rewards += $item['rewards'];
							$task_points += $item['task_points'];
						}	
						$earned = $rewards + $task_points;				
					}
					$all_time_list[$unique->referrer_id] = [$earned, $rewards, $task_points];
					arsort($all_time_list);
					$class->info('ALL TIME of User '.$i);
				}
				$i++;
				$main_bar->advance();
				
				$participants = new ReferralLeaderBoardParticipant;
				$participants->user_id = $unique->referrer_id;
				$participants->weekly_list = json_encode($this->weekly_participant_list);
				$participants->monthly_list = json_encode($this->monthly_participant_list);
				$participants->all_time_list = json_encode($this->all_time_participant_list);
				$participants->save();
			}
			$main_bar->finish();
			$class->info('DONE ALL USERS');
		}
		
		// TRUNCATING LEADERBOARD
		$board = new LeaderBoard;
		$board->truncate();

		// SAVING
		if (count($all_time_list) > 0) {
			$bar = $class->output->createProgressBar(count($all_time_list));
			foreach ($all_time_list as $key => $val) {
				$item = [
					$key => [$val[1], $val[2]]
				];
				$board = new LeaderBoard;
				$board->all_time_top = json_encode($item);
				$board->save();
				$bar->advance();
			}
			$bar->finish();
			$class->info('SAVING ALL TIME');
		}
		$i = 1;
		if (count($monthly_list) > 0) {
			$bar = $class->output->createProgressBar(count($monthly_list));
			foreach ($monthly_list as $key => $val) {
				$item = [
					$key => [$val[1], $val[2]]
				];
				$board = LeaderBoard::find($i);
				$board->monthly_top = json_encode($item);
				$board->save();
				$i++;
				$bar->advance();
			}
			$bar->finish();
			$class->info('SAVING MONTHLY');
		}
		$i = 1;
		if (count($weekly_list) > 0) {
			$bar = $class->output->createProgressBar(count($weekly_list));
			foreach ($weekly_list as $key => $val) {
				$item = [
					$key => [$val[1], $val[2]]
				];
				$board = LeaderBoard::find($i);
				$board->weekly_top = json_encode($item);
				$board->save();
				$i++;
				$bar->advance();
			}
			$bar->finish();
			$class->info('SAVING WEEKLY');
		}
		$class->line('End Sorting');
	}

	private function getUserEarnings($referrals, $class, $range=null)
	{
		$bar = $class->output->createProgressBar(count($referrals));
		$earned = 0;
		foreach ($referrals as $referral) {
			if ($referral->version == 1){
				$earned = $this->getUserEarningsAlgo($referral, $range);
			} elseif ($referral->version == 2){
				if ($referral->referral->status() == 'active'){
					$earned = $this->getUserEarningsAlgo($referral, $range);
				}
			}
			$bar->advance();
		}
		$bar->finish();
		return $earned;
	}

	private function getUserEarningsAlgo($referral, $range)
	{
		$new_earned_start = 0;
		$referral_rewards = 0;
		$task_points = 0;
		$earned = 0;
		if ($range == 'weekly'){
			$start = Carbon::now()->startOfWeek()->toDateTimeString();
			$end = Carbon::now()->endOfWeek()->toDateTimeString();
		} elseif ($range == 'monthly') {
			$start = Carbon::now()->startOfMonth()->toDateTimeString();
			$end = Carbon::now()->endOfMonth()->toDateTimeString();
		} else {
			$start = null;
			$end = null;
		}

		$referral_rewards = 0;
		$task_points = 0;
		$user_id = $referral->user_id;
		# GET REWARD OF REFERRAL USER
		$referral_rewards = $this->getReferralRewards($user_id, $start, $end);
		$earned += $referral_rewards;
		# GET FROM 1st LEVEL
		$task_points = $this->getTaskPoints($user_id, 1, $start, $end);
		$earned += $task_points;

		if ($earned > 0){
			$this->saveToParticipantListing($user_id, $referral_rewards, $task_points, $range, '1st');
		}
		# GET FROM 2nd LEVEL
		$referrals_2 = Referral::where('referrer_id', $user_id)->get();
		if (count($referrals_2) > 0) {
			foreach ($referrals_2 as $ref_2) {
				$user_id_2 = $ref_2->user_id;
				$task_points = $this->getTaskPoints($user_id_2, 2, $start, $end);
				if($task_points > 0){
					$earned += $task_points;
					$this->saveToParticipantListing($user_id_2, 0, $task_points, $range, '2nd');
				}
				# GET FROM 3rd LEVEL
				$referrals_3 = Referral::where('referrer_id', $user_id_2)->get();
				if (count($referrals_3) > 0) {
					foreach ($referrals_3 as $ref_3) {
						$user_id_3 = $ref_3->user_id;
						$task_points = $this->getTaskPoints($user_id_3, 3, $start, $end);
						if($task_points > 0){
							$earned += $task_points;
							$this->saveToParticipantListing($user_id_3, 0, $task_points, $range, '3rd');
 						}
					}
				}
			}
		}
		return $earned;
	}

	private function getReferralRewards($user_id, $start=null, $end=null)
	{
		if ($start != null AND $end != null){
			$rewards = ReferralReward::where('created_at', '>=', $start)->where('created_at', '<=', $end)->where('referral_id', $user_id)->sum('reward');
		} else {
			$rewards = ReferralReward::where('referral_id', $user_id)->sum('reward');
		}
		return $rewards;
	}

	private function getTaskPoints($user_id, $level=1, $start = null, $end = null)
	{
		$points = 0;
		# INITIALIZE POINT SYSTEM SETTINGS
		$referral_point_system = settings('referral_point_system')->value;
		$referral_point = settings('referral_point')->value;
		$referral_point_cap = settings('referral_point_cap')->value;

		# GET POINTS
		if ($start != null and $end != null) {
			$points = TaskUser::where('created_at', '>=', $start)->where('created_at', '<=', $end)->where('user_id', $user_id)->where('revoke', 0)->sum('reward');
		} else {
			$points = TaskUser::where('user_id', $user_id)->where('revoke', 0)->sum('reward');
		}
		# TAKE POINT ACCORDING TO POINT SYSTEM
		if ($referral_point_system == 'percentage') {
			$points = $points * ($referral_point / 100);
		} else {
			$points = $referral_point;
		}
		# CHECK POINT IF IN LIMIT
		if ($points > $referral_point_cap){
			$points = $referral_point_cap;
		}
		# BY LEVEL
		if ($level == 1){
			return $points;
		} elseif ($level == 2){
			return ($points * .50);
		} elseif ($level == 3) {
			return ($points * .25);
		}
	}

	private function saveToParticipantListing($user_id, $rewards=0, $task_points=0, $range=null, $level='1st')
	{
		$item = [
			'user_id' => $user_id,
			'rewards' => $rewards,
			'task_points' => $task_points,
			'level' => $level
		];
		if ($range == 'weekly'){
			array_push($this->weekly_participant_list, $item);
		} elseif ($range == 'monthly'){
			array_push($this->monthly_participant_list, $item);
		} else {
			array_push($this->all_time_participant_list, $item);
		}
	}


	public function referralCalculate(CalculateReferralPoints $class)
	{
		$class->line('Start Calculation');
		$referral_lists = Referral::where('status', '<>', 0)->get();
		$bar = $class->output->createProgressBar(count($referral_lists));

		if (count($referral_lists) > 0) {
			$class->line("Going 1st Level Referral");
			foreach ($referral_lists as $item) {
				if ($item->version == 1){
					$this->referralCalculationAlgo($item, $class);
				} elseif ($item->version == 2){
					if ($item->referral->status() == 'active'){
						$this->referralCalculationAlgo($item, $class);
					}
				}				
				$bar->advance();
			}
		}
		$bar->finish();
		$class->line('');
		$class->line('End Calculation');
	}

	private function referralCalculationAlgo($item, $class)
	{
		$points = $this->recalculateDirectReferral($item->user_id);
		if ($points > $this->direct_cap) {
			$points = $this->direct_cap;
		}
		$referral = Referral::find($item->id);
		$referral->points = $points;
		$referral->save();

		$second_lvl_items = Referral::where('referrer_id', $item->user_id)->where('status', '<>', 2)->get();
		if (count($second_lvl_items) > 0) {
			$class->line("Going 2nd Level Referral");
			foreach ($second_lvl_items as $sec_item) {
				if ($sec_item->version == 1){
					$second_lvl_points = $this->recalculateDirectReferral($sec_item->user_id);
					if ($second_lvl_points > $this->second_lvl_cap) {
						$second_lvl_points = $this->second_lvl_cap;
					}
					$referral = Referral::find($sec_item->id);
					$referral->second_lvl_points = $second_lvl_points;
					$referral->save();

					$third_lvl_items = Referral::where('referrer_id', $sec_item->user_id)->where('status', '<>', 2)->get();
					if (count($third_lvl_items) > 0) {
						$class->line("Going 3rd Level Referral");
						foreach ($third_lvl_items as $third_item) {
							$third_lvl_points = $this->recalculateDirectReferral($third_item->user_id);
							if ($third_lvl_points > $this->thrid_lvl_cap) {
								$third_lvl_points = $this->thrid_lvl_cap;
							}
							$referral = Referral::find($third_item->id);
							$referral->third_lvl_points = $third_lvl_points;
							$referral->save();
						}
					}
				} elseif ($sec_item->version == 2){
					if ($sec_item->referral->status() == 'active'){
						$second_lvl_points = $this->recalculateDirectReferral($sec_item->user_id);
						if ($second_lvl_points > $this->second_lvl_cap) {
							$second_lvl_points = $this->second_lvl_cap;
						}
						$referral = Referral::find($sec_item->id);
						$referral->second_lvl_points = $second_lvl_points;
						$referral->save();

						$third_lvl_items = Referral::where('referrer_id', $sec_item->user_id)->where('status', '<>', 2)->get();
						if (count($third_lvl_items) > 0) {
							$class->line("Going 3rd Level Referral");
							foreach ($third_lvl_items as $third_item) {
								if ($third_item->version == 1){
									$third_lvl_points = $this->recalculateDirectReferral($third_item->user_id);
									if ($third_lvl_points > $this->thrid_lvl_cap) {
										$third_lvl_points = $this->thrid_lvl_cap;
									}
									$referral = Referral::find($third_item->id);
									$referral->third_lvl_points = $third_lvl_points;
									$referral->save();
								} elseif ($third_item->version == 2){
									if ($third_item->referral->status() == 'active'){
										$third_lvl_points = $this->recalculateDirectReferral($third_item->user_id);
										if ($third_lvl_points > $this->thrid_lvl_cap) {
											$third_lvl_points = $this->thrid_lvl_cap;
										}
										$referral = Referral::find($third_item->id);
										$referral->third_lvl_points = $third_lvl_points;
										$referral->save();
									}
								}
							}
						}
					}
				}
			}
		}
	}

	private function recalculateDirectReferral($user_id)
	{
		$points = 0;
		$point_system = Settings::where('key', 'referral_point_system')->first();
		$point = Settings::where('key', 'referral_point')->first();
		if ($point_system) {
			if ($point) {
				$completes = TaskUser::where('user_id', $user_id)->where('revoke', 0)->get();
				if (count($completes) > 0) {
					foreach ($completes as $completed) {
						if ($point_system->value == 'predefined') {
							$points += (double)$point->value;
						} elseif ($point_system->value == 'percentage') {
							$points += (double)($completed->reward * ($point->value / 100));
						}
					}
				}
			}
		}
		return $points;
	}

	public function recalculateReferralReward($class)
	{
		$signup_reward = 0;
		$settings = Settings::where('key', 'signup_referral_reward')->first();
		if ($settings) {
			$signup_reward = $settings->value;
		}
		$social_connection_reward = 0;
		$settings = Settings::where('key', 'social_connection_reward')->first();
		if ($settings) {
			$social_connection_reward = $settings->value;
		}
		// GET REWARD FROM SIGNUP START
		$referrals = Referral::where('status', '>', 0)->get();
		$bar = $class->output->createProgressBar(count($referrals));
		if (count($referrals) > 0) {
			foreach ($referrals as $ref) {
				if ($ref->version == 1){
					set_referral_reward($ref->referrer_id, $ref->user_id, $signup_reward, 1);
					$social_connected = SocialConnect::where('user_id', $ref->user_id)->count();
					if ($social_connected > 0) {
						set_referral_reward($ref->referrer_id, $ref->user_id, $social_connection_reward, 2);
					}
				} elseif ($ref->version == 2){
					if ($ref->referral->status() == 'active'){
						set_referral_reward($ref->referrer_id, $ref->user_id, $signup_reward, 1);
						$social_connected = SocialConnect::where('user_id', $ref->user_id)->count();
						if ($social_connected > 0) {
							set_referral_reward($ref->referrer_id, $ref->user_id, $social_connection_reward, 2);
						}
					}
				}
				$bar->advance();
			}
		}
		$bar->finish();
		$class->line('End Calculating');
		// GET REWARD FROM SIGNUP END
	}

	public function saveBankHistory($class){

		# START BASIC & LEGDER
		$class->line('Start Retrieving of Bank Transaction');

		$i = 1;
		$history = [];
		$txids = RecTxid::pluck('recadd');
        $dbwithdrawals = DbWithdrawal::whereIn('recaddress',$txids)->get();
        if($dbwithdrawals){
            foreach($dbwithdrawals as $withdrawals){
                $rec_txidinfo = RecTxid::where('recadd',$withdrawals->recaddress)->get();

                if(count($rec_txidinfo) > 0){
                    foreach($rec_txidinfo as $key => $tx){

                        $data = [
							'trxn_id' => static::get_bank_transaction_by_name('basic'),
							'user_id' => $withdrawals->user_id,
							'trxn_type' => WDRAW,
							'amount' => $tx->coins,
							'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$tx->date)->toDateTimeString()
                        ];

						array_push($history,$data);
						$class->info('Basic and Ledger Deposit '.$i);
						$i++;
                    }
				}
            }
		}
		# END BASIC & LEDGER

		# START REFERRAL/POINTS
		$referral_reward_deposit = ReferralReward::orderByDesc('created_at')->get();
		if($referral_reward_deposit){
            foreach($referral_reward_deposit as $depo){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('referral'),
					'user_id' => $depo->user_id,
					'trxn_type' => DEPO,
					'amount' => $depo->reward,
					'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$depo->created_at)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Referral Reward Deposit '.$i);
				$i++;
            }
		}

		$referral_reward_withdraw = ReferralReward::orderByDesc('created_at')->get();
		if($referral_reward_withdraw){
            foreach($referral_reward_withdraw as $withdraw){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('referral'),
					'user_id' => $withdraw->referral_id,
					'trxn_type' => WDRAW,
					'amount' => $withdraw->reward,
					'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->created_at)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Referral Reward Withdraw '.$i);
				$i++;
            }
		} 

		// $referrals = [];
		// $group_list = ['weekly_list','monthly_list','all_time_list'];
		// $referral = Referral::where('status',2)->get();
		// if(count($referral) > 0){
		// 	foreach($referral as $ref){
		// 		$participants = ReferralLeaderBoardParticipant::where('user_id',$ref->referrer_id)->first();
		// 		$top = [];
		// 		 if($participants <> null){
		// 			 foreach($group_list as $key => $val){
		// 				if($participants->$val <> '[]'){
		// 					 $top[] = json_decode($participants->$val,true);
		// 				}
		// 			 }
		// 		 }    
		// 		 $this->count = 0;
		// 		 foreach($top as $key => $val){
		// 			 foreach($val as $reff){
		// 				 $checker = TaskUser::where('user_id',$reff['user_id'])->get();
		// 				 if(count($checker) > 0 && $this->count < 100){
		// 					array_push($referrals,$reff);
		// 					$referrals = array_unique($referrals,SORT_REGULAR);
		// 					$this->count++;
		// 				 }
		// 			 }
		// 		 }

		// 		 if(count($referrals) > 0){
		// 			foreach($referrals as $depo){
		// 				$task_user = TaskUser::with(['taskInfo','user'])
		// 									->where('user_id',$depo['user_id'])->offset(0)->limit(100)->get();
						
		// 				if(count($task_user) > 0){
		// 					foreach($task_user as $key => $val){
		// 						$data = [
		// 							'trxn_id' => static::get_bank_transaction_by_name('task referral points'),
		// 							'user_id' => $ref->referrer_id,
		// 							'trxn_type' => DEPO,
		// 							'amount' => $val->reward,
		// 							'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$val->created_at)->toDateTimeString()
		// 						];
				
		// 						array_push($history,$data);
		// 						$class->info('Referral Points all level '.$i);
		// 						$i++;
		// 					}
		// 				}
						
		// 			}
		// 		}

		// 	}
		// }
		# END REFERRAL/POINTS

		# START TASK EARNED/REWARD
		$task_reward_completed =  DB::table('task_transaction_histories as a')
								->leftjoin('tasks as b', 'b.id', '=', 'a.task_id')
								->leftjoin('task_user as u',function($join){
									$join->on('u.user_id','=','a.user_id');
									$join->on('u.task_id','=','a.task_id');
								})
								->select([
									'u.created_at as completed_dt',
									'a.*',
									'b.*'
								])
								->where('transaction_type', 'completion')
								->orderBy('u.created_at','desc')->offset(0)->limit(1000)->get();
		
		if(count($task_reward_completed) > 0){ 
			foreach($task_reward_completed as $depo){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('task earned'),
					'user_id' => $depo->user_id,
					'trxn_type' => DEPO,
					'amount' => $depo->reward,
					'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$depo->completed_dt)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Task Completed Rewards '.$i);
				$i++;
			}
		}

		$task_reward_revoked_wdraw = DB::table('task_transaction_histories as a')
								->leftjoin('tasks as b', 'b.id', '=', 'a.task_id')
								->leftjoin('task_user as u',function($join){
									$join->on('u.user_id','=','a.user_id');
									$join->on('u.task_id','=','a.task_id');
								})
								->leftjoin('banned_user_task as c',function($join){
									$join->on('u.user_id','=','c.user_id');
									$join->on('u.task_id','=','c.task_id');
								})
								->select([
									'c.created_at as revoked_dt',
									'a.*',
									'b.*'
								])->where('transaction_type', 'revoked')->get();
		
		if($task_reward_revoked_wdraw){
			foreach($task_reward_revoked_wdraw as $withdraw){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('task earned'),
					'user_id' => $withdraw->user_id,
					'trxn_type' => WDRAW,
					'amount' => $withdraw->reward,
					'trxn_date' => ($withdraw->revoked_dt == null) ? Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->created_at)->toDateTimeString() : Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->revoked_dt)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Task Revoked Reward Withdraw '.$i);
				$i++;
			}
		}

		$task_reward_revoked_depo = DB::table('task_transaction_histories as a')
								->leftjoin('tasks as b', 'b.id', '=', 'a.task_id')
								->leftjoin('task_user as u',function($join){
									$join->on('u.user_id','=','a.user_id');
									$join->on('u.task_id','=','a.task_id');
								})
								->leftjoin('banned_user_task as c',function($join){
									$join->on('u.user_id','=','c.user_id');
									$join->on('u.task_id','=','c.task_id');
								})
								->select([
									'c.created_at as revoked_dt',
									'b.id as task_creator',
									'a.*',
									'b.*'
								])->where('transaction_type', 'revoked')->get();
		
		if($task_reward_revoked_depo){
			foreach($task_reward_revoked_depo as $withdraw){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('task earned'),
					'user_id' => $withdraw->task_creator,
					'trxn_type' => DEPO,
					'amount' => $withdraw->reward,
					'trxn_date' => ($withdraw->revoked_dt == null) ? Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->created_at)->toDateTimeString() : Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->revoked_dt)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Task Revoked Reward Deposit '.$i);
				$i++;
			}
		}

		$task_withdrawal = DB::table('task_user as a')
									->leftjoin('users as b', 'b.id', '=', 'a.user_id')
									->leftjoin('tasks as c', 'c.id', '=', 'a.tasK_id')
									->select([
										'a.created_at as completed_date',
										'a.*',
										'c.*'
									])->where('a.revoke', 0)->offset(0)->limit(1000)->get();

		if($task_withdrawal){
			foreach($task_withdrawal as $withdraw){
				$data = [
					'trxn_id' => static::get_bank_transaction_by_name('task earned'),
					'user_id' => $withdraw->task_creator,
					'trxn_type' => WDRAW,
					'amount' => $withdraw->reward,
					'trxn_date' => Carbon::createFromFormat('Y-m-d H:i:s',$withdraw->completed_date)->toDateTimeString()
				];

				array_push($history,$data);
				$class->info('Task Withdrawal '.$i);
				$i++;
			}
		}
		 # END TASK REWARD

		 $class->info('DONE FETCHING ALL BANK TRANSACTIONS');
		


		$bar = $class->output->createProgressBar(count($history));

		$trans_date = array();
		foreach($history as $key => $row){
			$trans_date[$key] = $row['trxn_date'];
		}
		array_multisort($trans_date,SORT_ASC,$history);

		$class->line('Start Saving of Bank Transaction');
		$bank_history = new BankTransactionHistory;
		$bank_history->truncate();
		foreach($history as $hist){
			$saveok = static::save_bank_transaction_history($hist);
			$bar->advance();
		}
		$bar->finish();
		$class->line('End Saving of Bank History');

	}

     public function settingsDefaults($class)
	{
		$class->line('> referral_point_system');
		$settings = Settings::where('key', 'referral_point_system')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = "referral_point_system";
			$settings->value = "percentage";
			$settings->description = "Point System on how to calculate earnings";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> referral_point');
		$settings = Settings::where('key', 'referral_point')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = "referral_point";
			$settings->value = 0;
			$settings->description = "Point according to point system";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> referral_point_cap');
		$settings = Settings::where('key', 'referral_point_cap')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = "referral_point_cap";
			$settings->value = 10;
			$settings->description = "Referral Point Cap/Limit from Direct Referral devide by half after next levels";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> signup_referral_reward');
		$settings = Settings::where('key', 'signup_referral_reward')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'signup_referral_reward';
			$settings->value = 25;
			$settings->description = "Referrer reward after referral's account have been confirmed";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> social_connection_reward');
		$settings = Settings::where('key', 'social_connection_reward')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'social_connection_reward';
			$settings->value = 75;
			$settings->description = "Referrer reward after referral's account connected atleast one social account";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> ip_block_duration');
		$settings = Settings::where('key', 'ip_block_duration')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'ip_block_duration';
			$settings->value = 7;
			$settings->description = "Days duration to block ip address";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> domain_block_duration');
		$settings = Settings::where('key', 'domain_block_duration')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'domain_block_duration';
			$settings->value = 7;
			$settings->description = "Days duration to block domain";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> address_block_duration');
		$settings = Settings::where('key', 'address_block_duration')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'address_block_duration';
			$settings->value = 7;
			$settings->description = "Days duration to block SUP Address";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> bank_on_hold_duration');
		$settings = Settings::where('key', 'bank_on_hold_duration')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'bank_on_hold_duration';
			$settings->value = 7;
			$settings->description = "Days duration to Hold Payouts";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> enable_gift_coin');
		$settings = Settings::where('key', 'enable_gift_coin')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'enable_gift_coin';
			$settings->value = 1;
			$settings->description = "Enable/Disable Gift Coin";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> withdrawal_limit_amount_per_day');
		$settings = Settings::where('key', 'withdrawal_limit_amount_per_day')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'withdrawal_limit_amount_per_day';
			$settings->value = 100000;
			$settings->description = "Max Amount allowed for Withdrawal per day";
			$settings->save();
			$class->line('> setted');
		}

		$class->line('> user_account_duration_allow_withdrawal');
		$settings = Settings::where('key', 'user_account_duration_allow_withdrawal')->first();
		if ($settings == null) {
			$settings = new Settings;
			$settings->key = 'user_account_duration_allow_withdrawal';
			$settings->value = 30;
			$settings->description = "Days duration for user account to allow withdrawal";
			$settings->save();
			$class->line('> setted');
		}
	}

	public function sortOwnLeaderBoard($class){

		$class->line('Start Sorting');
		$unique_users = Referral::groupBy('referrer_id')->where('status', '<>', 0)->get();

		$group_list = [ 'weekly_top' => 'weekly_list',
						'monthly_top' => 'monthly_list',
						'all_time_top' => 'all_time_list' ];
		
		$level_list = [ '1st' => 'direct',
						'2nd' => 'second',
						'3rd' => 'third' ];

		if(count($unique_users) > 0){
			$main_bar = $class->output->createProgressBar(count($unique_users));
			foreach($unique_users as $key1 => $user){
				$user_id = $user->referrer_id;
				$participants = ReferralLeaderBoardParticipant::where('user_id',$user_id)->first();
				# start group list
				$weekly_list = [];
				$monthly_list = [];
				$all_time_list = [];
				$weekly_rank = 0;
				$monthly_rank = 0;
				$all_time_rank = 0;
				$referrals = [];
				foreach ($group_list as $key => $group){
					$class->info('Sort own leaderboard: '.$group. ' of User '.$user_id);
					if($participants->$group <> '[]'){
						$referrals[] = json_decode($participants->$group,true);
						$top = json_decode($participants->$group);
						foreach($top as $key2 => $value){
							$param = ['col' => $key, 'userid' => $value->user_id];
							$leader_info = LeaderBoard::getLeaderBoardInfoByRange($param);
							$rank = "";
							$reward = 0;
							$task_points = 0;
							if($leader_info){
								$rank = $leader_info['rank'];
								$reward = $leader_info['reward'];
								$task_points = $leader_info['task_points'];
							}

							$ref_count_first = 0;
							$ref_count_second = 0;
							$ref_count_third = 0;
							foreach($level_list as $key3 => $level){
								$ref_param = ['level' => $key3, 'group_list' => $group, 'user_id' => $value->user_id];
								$referral_cnt = ReferralLeaderBoardParticipant::getReferralCountByLevel($ref_param);
								if($key3 == '1st'){
									$ref_count_first = $referral_cnt;
								}else if($key3 == '2nd'){
									$ref_count_second = $referral_cnt;
								}else if($key3 == '3rd'){
									$ref_count_third = $referral_cnt;
								}
							}	
							$earned = $reward + $task_points;
							$item = [
								'rank' => $rank,
								'user_id' => $value->user_id,
								'rewards' => $reward,
								'task_points' => $task_points,
								'total_earned' => $earned,
								'ref_count_first' => $ref_count_first,
								'ref_count_second' => $ref_count_second,
								'ref_count_third' => $ref_count_third
							];

							if($rank <> '' && $earned > 0){
								if($group == 'weekly_list'){
									array_push($weekly_list, $item);
								}elseif($group == 'monthly_list'){
									array_push($monthly_list, $item);
								}elseif($group == 'all_time_list'){
									array_push($all_time_list, $item);
								}
							}
						}
					}	
					
					$param = ['col' => $key, 'userid' => $user_id];
					$user_rank = LeaderBoard::getLeaderBoardRankByRange($param);
					if($group == 'weekly_list'){
						$weekly_rank = $user_rank;
					}elseif($group == 'monthly_list'){
						$monthly_rank = $user_rank;
					}elseif($group == 'all_time_list'){
						$all_time_rank = $user_rank;
					}

				} # end group list

				$all_time_total = array();
				if(count($all_time_list) > 0){
					foreach($all_time_list as $key => $row){
						$all_time_total[$key] = $row['total_earned'];
					}
					array_multisort($all_time_total,SORT_DESC,$all_time_list);
				}

				$monthly_total = array();
				if(count($monthly_list) > 0){
					foreach($monthly_list as $key => $row){
						$monthly_total[$key] = $row['total_earned'];
					}
					array_multisort($monthly_total,SORT_DESC,$monthly_list);
				}

				$weekly_total = array();
				if(count($weekly_list) > 0){
					foreach($weekly_list as $key => $row){
						$weekly_total[$key] = $row['total_earned'];
					}
					array_multisort($weekly_total,SORT_DESC,$weekly_list);
				}

				$direct_list = [];
				$second_list = [];
				$third_list = [];
				foreach($referrals as $key => $val){
					foreach($val as $ref){
						$class->info('Sort referrals of User '.$ref['user_id']);
						foreach($level_list as $key2 => $level){
							$ref_param = ['level' => $key2, 'group_list' => '', 'user_id' => $ref['user_id'] ];
							$ref_cnt = ReferralLeaderBoardParticipant::getReferralCountByLevel($ref_param);
							$user_info = User::find($ref['user_id']);
							$reg_date = "";
							if($user_info){
								$reg_date = Carbon::parse($user_info->created_at)->toDateTimeString();
							}
							if($key2 == '1st'){
								if($ref['level'] == $key2){
									$item = [
										'user_id' => $ref['user_id'],
										'rewards' => $ref['rewards'],
										'task_points' => $ref['task_points'],
										'level' => $ref['level'],
										'ref_cnt' => $ref_cnt,
										'reg_date' => $reg_date
									];
									array_push($direct_list,$item);
									$direct_list = array_unique($direct_list,SORT_REGULAR);
									
								}
							}elseif($key2 == '2nd'){
								if($ref['level'] == $key2){
									$item = [
										'user_id' => $ref['user_id'],
										'rewards' => $ref['rewards'],
										'task_points' => $ref['task_points'],
										'level' => $ref['level'],
										'ref_cnt' => $ref_cnt,
										'reg_date' => $reg_date
									];
									array_push($second_list,$item);
									$second_list = array_unique($second_list,SORT_REGULAR);
								}
							}elseif($key2 == '3rd'){
								if($ref['level'] == $key2){
									$item = [
										'user_id' => $ref['user_id'],
										'rewards' => $ref['rewards'],
										'task_points' => $ref['task_points'],
										'level' => $ref['level'],
										'ref_cnt' => $ref_cnt,
										'reg_date' => $reg_date
									];
									array_push($third_list,$item);
									$third_list = array_unique($third_list,SORT_REGULAR);
								}
							}
						}
					}
				}
				
				$direct_list = $this->unique_multidim_array($direct_list,'user_id');
				$second_list = $this->unique_multidim_array($second_list,'user_id');
				$third_list = $this->unique_multidim_array($third_list,'user_id');

				$first_total = array();
				if(count($direct_list) > 0){
					foreach($direct_list as $key => $row){
						$first_total[$key] = $row['reg_date'];
					}
					array_multisort($first_total,SORT_DESC,$direct_list);
				}
				
				$second_total = array();
				if(count($second_list) > 0){
					foreach($second_list as $key => $row){
						$second_total[$key] = $row['reg_date'];
					}
					array_multisort($second_total,SORT_DESC,$second_list);
				}

				$third_total = array();
				if(count($third_list) > 0){
					foreach($third_list as $key => $row){
						$third_total[$key] = $row['reg_date'];
					}
					array_multisort($third_total,SORT_DESC,$third_list);
				}

				$direct_level_signup_week = 0;
				$second_level_signup_week = 0;
				$third_level_signup_week = 0;
				$direct_level_signup_month = 0;
				$second_level_signup_month = 0;
				$third_level_signup_month = 0;
				$direct_level_signup_alltime = 0;
				$second_level_signup_alltime = 0;
				$third_level_signup_alltime = 0;
				$referral_count = [];
				foreach ($group_list as $keyy => $group){
					foreach($level_list as $key => $level){
						$ref_param = ['level' => $key, 'group_list' => $group, 'user_id' => $user_id ];
						$ref_cnt = ReferralLeaderBoardParticipant::getReferralCountByLevel($ref_param);

						if($group == 'weekly_list'){
							if($key == '1st'){
								$direct_level_signup_week = $ref_cnt;
							}else if($key == '2nd'){
								$second_level_signup_week = $ref_cnt;
							}else if($key == '3rd'){
								$third_level_signup_week = $ref_cnt;
							}
						}elseif($group == 'monthly_list'){
							if($key == '1st'){
								$direct_level_signup_month = $ref_cnt;
							}else if($key == '2nd'){
								$second_level_signup_month = $ref_cnt;
							}else if($key == '3rd'){
								$third_level_signup_month = $ref_cnt;
							}
						}elseif($group == 'all_time_list'){
							if($key == '1st'){
								$direct_level_signup_alltime = $ref_cnt;
							}else if($key == '2nd'){
								$second_level_signup_alltime = $ref_cnt;
							}else if($key == '3rd'){
								$third_level_signup_alltime = $ref_cnt;
							}
						}

						$referral_count = [
							'direct_level_signup_week' => $direct_level_signup_week,
							'second_level_signup_week' => $second_level_signup_week,
							'third_level_signup_week' => $third_level_signup_week,
							'direct_level_signup_month' => $direct_level_signup_month,
							'second_level_signup_month' => $second_level_signup_month,
							'third_level_signup_month' => $third_level_signup_month,
							'direct_level_signup_alltime' => $direct_level_signup_alltime,
							'second_level_signup_alltime' => $second_level_signup_alltime,
							'third_level_signup_alltime' => $third_level_signup_alltime,
						];
					}
				}
				
				$main_bar->advance();
				$date_now = date('Y-m-d');
				$own_leaderboard = LeaderBoardOwn::where('user_id',$user_id)->whereDate('created_at',$date_now)->orderByDesc('created_at')->first();
				if($own_leaderboard <> null){
					$own_leaderboard->user_id = $user_id;
					$own_leaderboard->referral_count = json_encode($referral_count);
					$own_leaderboard->all_time_list = json_encode($all_time_list);
					$own_leaderboard->monthly_list = json_encode($monthly_list);
					$own_leaderboard->weekly_list = json_encode($weekly_list);
					$own_leaderboard->weekly_rank = $weekly_rank;
					$own_leaderboard->monthly_rank = $monthly_rank;
					$own_leaderboard->all_time_rank = $all_time_rank;
					$own_leaderboard->direct_referral_list = json_encode($direct_list);
					$own_leaderboard->second_referral_list = json_encode($second_list);
					$own_leaderboard->third_referral_list = json_encode($third_list);
					$own_leaderboard->save();
				}else{
					$own_leaderboard = new LeaderBoardOwn();
					$own_leaderboard->user_id = $user_id;
					$own_leaderboard->referral_count = json_encode($referral_count);
					$own_leaderboard->all_time_list = json_encode($all_time_list);
					$own_leaderboard->monthly_list = json_encode($monthly_list);
					$own_leaderboard->weekly_list = json_encode($weekly_list);
					$own_leaderboard->weekly_rank = $weekly_rank;
					$own_leaderboard->monthly_rank = $monthly_rank;
					$own_leaderboard->all_time_rank = $all_time_rank;
					$own_leaderboard->direct_referral_list = json_encode($direct_list);
					$own_leaderboard->second_referral_list = json_encode($second_list);
					$own_leaderboard->third_referral_list = json_encode($third_list);
					$own_leaderboard->save();
				}
			}
		}

		$main_bar->finish();
		$class->info('DONE ALL USERS');
	}

	function unique_multidim_array($array, $key) { 
		$temp_array = array(); 
		$i = 0; 
		$key_array = array(); 
		
		foreach($array as $val) { 
			if (!in_array($val[$key], $key_array)) { 
				$key_array[$i] = $val[$key]; 
				$temp_array[$i] = $val; 
			}
			$i++; 
		} 
		return $temp_array; 
	} 

	public function sendSupPayment() {
        #SET TEMPORARY STATUS
        $checklast = DbWithdrawal::where('status', 1)->orderBy('created_at', 'desc')->first();

        if($checklast != null){
            $checklast->status = 101;
            $checklast->save();

            #CHANGE ALL STATUS 1 and 0 to 9
            $other_statuses = DbWithdrawal::where(function($q){
                $q->where('status', 1)->orWhere('status', 0)->orWhere('status', 10);
            })->where('user_id', $checklast->user_id)->get();

            foreach ($other_statuses as $item){
                $other = DbWithdrawal::find($item->id);
                $other->status = 9;
                $other->save();
            }

            $hostname = config('app.wallet.ip');
            $port = 8082;
            $wallet2 = new Wallet($hostname, $port);
            $wallet = new Wallet(config('app.wallet.ip'));
            $height = $wallet->getHeight();
            $height = json_decode($height);
            $height = $height->height;

            $checklast->status = 12;
            $checklast->save();

            $withdrawal = new Withdrawal();
            $withdrawal->user_id = $checklast->user_id;
            $withdrawal->balance = $checklast->balance;
            $withdrawal->block = $height;
            $withdrawal->transid = $checklast->id;
            $withdrawal->address = $checklast->address;
            $withdrawal->sendaddress = $checklast->sendaddress;
            $withdrawal->status = 0;
            $withdrawal->txid = 0;
            $withdrawal->type = 0;
            $withdrawal->save();

            $checklast->status = 13;
            $checklast->save();


            $options = [
                'destinations' => (object) [
                    'amount' => $checklast->balance,
                    'address' => $checklast->sendaddress
                ]
            ];

            $tx_hash = $wallet->transferSplit($options);
            $tx_hash = json_decode($tx_hash);

			if(array_key_exists('code', $tx_hash)){
				$checklast->error_code = $tx_hash->code;
			}
			if(array_key_exists('message', $tx_hash)){
				$checklast->error_msg = $tx_hash->message;
			}    
			
			$checklast->status = 14;
			$checklast->save();
			
			if(array_key_exists('tx_hash_list', $tx_hash)){
				$tx_hash = $tx_hash->tx_hash_list;
				foreach ($tx_hash as $hash){
					$txid = new Txid();
					$txid->transid = $checklast->id;
					$txid->type = 0;
					$txid->txids = $hash;
					$txid->user_id = $checklast->user_id;
					$txid->save();
				}

				$checklast->status = 15;
				$checklast->save();

				$withdrawal2 = new Withdrawal();
				$withdrawal2->user_id = $checklast->user_id;
				$withdrawal2->balance = $checklast->balance;
				$withdrawal2->block = $height;
				$withdrawal2->transid = $checklast->id;
				$withdrawal2->address = $checklast->address;
				$withdrawal2->sendaddress = $checklast->recaddress;
				$withdrawal2->status = 0;
				$withdrawal2->txid = 0;
				$withdrawal2->type = 1;
				$withdrawal2->save();

				$checklast->status = 16;
				$checklast->save();

				$paymentid = null;

				if(isset($checklast->paymentid)){
					$paymentid = $checklast->paymentid;
					$options = [
						'destinations' => (object) [
							'amount' => $checklast->balance,
							'address' => $checklast->recaddress,
						],
						'payment_id' => $paymentid
					];
				}else{
					$options = [
						'destinations' => (object) [
							'amount' => $checklast->balance,
							'address' => $checklast->recaddress
						]
					];
				}

				$tx_hash = $wallet2->transferSplit($options);
				$tx_hash = json_decode($tx_hash);

				$checklast->status = 18;
				if(array_key_exists('message', $tx_hash)){
					$checklast->error_msg = $tx_hash->message;
				}

				if(array_key_exists('code', $tx_hash)){
					$checklast->error_code = $tx_hash->code;
					if($tx_hash->code == -4){
						$checklast->status = 17;
					}
				}
				
				$checklast->save();

				if(array_key_exists('tx_hash_list', $tx_hash)){
					$tx_hash2 = $tx_hash->tx_hash_list;
					foreach ($tx_hash2 as $hash){
						$txid = new Txid();
						$txid->transid = $checklast->id;
						$txid->type = 1;
						$txid->txids = $hash;
						$txid->user_id = $checklast->user_id;
						$txid->save();
					}
	
					$checklast->status = 2;
					$checklast->save();        
				}
			}
        }
	}
	
	public function bonus(){
		$total = 0;
        $users = User::get();
        $walletRepository = new WalletRepository();
        $fb = 0;
        $multiplier = Settings::select('value')->where('key','bonus_multiplier')->first();
        $sub_days = Settings::select('value')->where('key','bonus_sub_days')->first();
		// $bonus_date = Carbon::now()->subDays($sub_days->value);
		$bonus_date = Carbon::now()->subDays(12);
		$coins = (new Bonus)->month_coins();
		
		$bonusDate = $bonus_date->toDateString();

        $selected_month = '';
        foreach($coins as $key => $coin){
            if($bonus_date->month == $key){
                $selected_month = $coin;
            }
		}

        foreach ($users as $user) {
			$id = $user->id;
			$bank = Bank::where("user_id", "=", $id)->first();
            if ($bank) {
                $holdings = $walletRepository->getholdings($id);
                $bank->balance = $holdings['total'];
                $bank->update();
                
                $tot_balance = $holdings['total'];
                if($holdings['total'] < 0){
                    $tot_balance = 0;
                }
                
                $total = $total + $tot_balance;

				$bonus = Bonus::where('user_id', '=', $id)->first();
			
                if ($bonus) {
					$BonusDate2 = Carbon::parse($bonus->updated_at)->toDateString();
                    $add = $tot_balance * $multiplier->value;
                    $bonus->coins = $bonus->coins + $add;
                    $bonus->$selected_month = $add;
					$bonus->updated_at = $bonus_date;
					if($BonusDate2 <> $bonusDate){
						$bonus->save();
					}
                    $fb = $add + $fb;
                } else {
                    $bonus = new Bonus();
                    $bonus->user_id = $id;
                    $add = $tot_balance * $multiplier->value;
                    $bonus->coins = $add;
                    $bonus->$selected_month = $add;
                    $bonus->updated_at = $bonus_date;
					$bonus->save();
					$fb = $add + $fb;
                }

                $bonus_trans = BonusTransactions::where('user_id',$id)
												->where('month',$bonus_date->format('F'))
												->where('year', '=', $bonus_date->year)
                                                ->first();

				if($bonus_trans <> null){
					$bonus_trans->coins = $bonus->$selected_month;
					$bonus_trans->updated_at = $bonus_date;
					$bonus_trans->save();
				}else{
					$bonus_trans = new BonusTransactions();
					$bonus_trans->user_id = $id;
					$bonus_trans->coins = $bonus->$selected_month;
					$bonus_trans->month_coin = $selected_month;
					$bonus_trans->created_at = $bonus_date;
					$bonus_trans->updated_at = $bonus_date;
					$bonus_trans->month = $bonus_date->format('F');
					$bonus_trans->year = $bonus_date->year;
					$bonus_trans->save();
				}
                
               dump('Calculate Bonus of user_id '.$id);
            }
		}
		
        dump('Done all users!');
	}

	public function sendEmailVerificationReminder()
    {
        $weeK_ago = Carbon::now()->subDays(7)->toDateTimeString();
        $users = User::where('verified', 0)->where('ban', 0)->where('created_at', '>=', $weeK_ago)->get();
        $sent = 0;
        if (count($users)>0){
            foreach ($users as $user) {
                $now = Carbon::now();
                $registration = Carbon::createFromFormat('Y-m-d H:i:s', $user->created_at);
                $diff = $now->diffInDays($registration);
                if ($diff == 3 || $diff == 5 || $diff == 7){
                    Mail::to($user->email)->send(new EmailVerification($user));
                    $sent++;
                }
            }
        }
        return count($users)." Unverified Users and ".$sent." Email Sent";
	}
	
	public function checkSecurityItems()
	{
		dump('Start Checking Items');
		$domains = BlockedDomain::where('status', 1)->get();
		if (count($domains) > 0) {
			foreach ($domains as $domain) {
				$users = User::where('email', 'LIKE', '%@' . $domain->domain)->get(['id']);
				if (count($users) > 0) {
					foreach ($users as $user) {
						$affected = User::find($user->id);
							$affected->ban = 1;
							$affected->save();
 						}
					}
				dump(count($users) . ' Users affected with ' . $domain->domain);
			}
			dump(count($domains) . ' Domains listed');
		}
		dump('End Checking Items');
	}
}
