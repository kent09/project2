<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Traits\Manager\UserTrait;
use App\Model\Referral;
use App\User;

class SaveReferral implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, UserTrait;
    protected $users;
    protected $ref_code;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $users, $ref_code)
    {
        $this->users = $users;
        $this->ref_code = $ref_code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $referrer = static::referrer_by_ref_code($this->ref_code);
        if ($referrer != null){
            $args = [
                'user_id' => $this->users->id,
                'referrer_id' => $referrer->id,
            ];
            static::create_referral($args);
        }
    }
}
