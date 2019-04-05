<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Model\MembershipWithdrawal;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMembershipWithdrawalConfirmation extends Mailable
{
    use Queueable, SerializesModels;
    public $withdrawal;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(MembershipWithdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Membership Earnings Withdrawal Confirmation')->markdown('emails.withdrawal.membership');
    }
}
