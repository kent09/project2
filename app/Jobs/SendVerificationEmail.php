<?php

namespace App\Jobs;

use App\Model\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use Mail;

class SendVerificationEmail extends Job
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 120;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new EmailVerification($this->user);
        Mail::to($this->user->email)->send($email);
    }

    public function retryUntil()
    {
        return Carbon::now()->addSeconds(5);
    }
}
