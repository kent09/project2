<?php

namespace App\Contracts\Membership;

interface MembershipInterface
{
    public function list_roles($req);

    public function apply($req);

    public function application_status($req);

    public function use_code($req);

    public function check_code($req);

    public function get_user_limitations($req);
}