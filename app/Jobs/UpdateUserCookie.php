<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use App\Traits\Manager\UserTrait;
use App\Model\UserCookie;

class UpdateUserCookie implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, UserTrait;
    protected $user_id;
    protected $on_reg;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id=0, $on_reg=0)
    {
        $this->user_id = $user_id;
        $this->on_reg = $on_reg;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (Cookie::get('kryptonia_cookie')){
            if ($this->user_id == 0){
                $this->user_id = Auth::id();
            }
            $args = [
                'user_id' => $this->user_id,
                'cookie' => Cookie::get('kryptonia_cookie'),
                'on_reg' => $this->on_reg,
            ];
            static::create_cookie($args);
        }
    }
}
