<?php

namespace App\Mail;

use App\User;
use App\Model\DbWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Model\EmailWithdrawal;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class WithdrawalMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $withdrawal;
    public $email_withdrawal;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(DbWithdrawal $withdrawal, EmailWithdrawal $email_withdrawal, User $user)
    {
        $this->withdrawal = $withdrawal;
        $this->email_withdrawal = $email_withdrawal;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->subject('New referral sign up on Kryptonia.io');

        // return $this->from(['address' => config('app.no_reply'), 'name' => 'No Reply'])->subject(config('app.name') . ' Withdrawal')->view('emails.withdrawal.verification', ['user'=>$this->user, 'withdrawal'=>$this->withdrawal, 'email_withdrawal' => $this->email_withdrawal]);

        return $this->view('emails.withdrawal.verification')->with([
            'user'=>$this->user, 'withdrawal'=>$this->withdrawal, 'email_withdrawal' => $this->email_withdrawal
        ]);
    }
}
