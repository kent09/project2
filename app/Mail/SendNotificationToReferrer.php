<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;

class SendNotificationToReferrer extends Mailable
{
    use Queueable, SerializesModels;
    protected $user;
    protected $referrer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, User $referrer)
    {
        $this->user = $user;
        $this->referrer = $referrer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New referral sign up on Kryptonia.io')->view('emails.referral.referral', ['user'=>$this->user, 'referrer'=>$this->referrer]);
    }
}
