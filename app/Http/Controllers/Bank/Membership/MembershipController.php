<?php

namespace App\Http\Controllers\Bank\Membership;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Bank\Membership\MembershipInterface;

class MembershipController extends Controller
{
    protected $membership;

    public function __construct(MembershipInterface $membership)
    {
        $this->membership = $membership;
    }

    public function balance()
    {
        return $this->membership->balance(request());
    }

    public function request_withdraw()
    {
        return $this->membership->request_withdraw(request());
    }

    public function confirm_withdrawal($key)
    {
        return $this->membership->confirm_withdrawal($key);
    }

    public function withdrawal_history()
    {
        return $this->membership->withdrawal_history(request());
    }

    public function billing_history()
    {
        return $this->membership->billing_history(request());
    }
}
