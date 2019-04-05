<?php

namespace App\Contracts\Bank\Membership;

interface MembershipInterface
{
    public function balance($req);

    public function request_withdraw($req);

    public function confirm_withdrawal($key);

    public function withdrawal_history($req);

    public function billing_history($req);
}