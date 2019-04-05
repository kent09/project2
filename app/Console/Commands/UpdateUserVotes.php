<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\Poll;
use App\Model\Option;
use App\Model\UserVote;
use App\Model\VoteValue;
use App\Repository\WalletRepository;

class UpdateUserVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votes:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating User Votes Values for Active Polls';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Start Updating Vote Values');
        $this->line('Checking Active Polls');
        $polls = Poll::where('status', 1)->get();
        if (count($polls)>0){
            $DataRepo = new WalletRepository;
            foreach ($polls as $poll) {
                $this->line('->Getting Poll Options');
                foreach ($poll->options as $option) {
                    $value = 0;
                    $user_votes = UserVote::where('poll_id', $poll->id)->where('option_id', $option->id)->get();
                    if (count($user_votes)>0){
                        $this->line('-->Recalculating Vote Values');
                        foreach ($user_votes as $user_vote) {
                            $holdings = $DataRepo->getholdings($user_vote->user_id); // independent from api
                            $value += $holdings['total'];
                        }
                        $vote_value = VoteValue::where('poll_id', $poll->id)->where('option_id', $option->id)->first();
                        $vote_value->votes = $value;
                        $vote_value->save();
                        $this->info('-->Vote value Updated');
                    }                    
                }
            }
        }            
        $this->info('End Updating Vote Values');
    }
}
