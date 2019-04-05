<?php

namespace App\Http\Controllers\Membership;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Membership\MembershipInterface;

class MembershipController extends Controller
{
    protected $membership;

    public function __construct(MembershipInterface $membership)
    {
        $this->membership = $membership;
    }

    public function list_roles()
    {
        return $this->membership->list_roles(request());
    }

    public function apply()
    {
        return $this->membership->apply(request());
    }

    public function application_status()
    {
        return $this->membership->application_status(request());
    }

    public function use_code()
    {
        return $this->membership->use_code(request());
    }

    public function check_code()
    {
        return $this->membership->check_code(request());
    }

    public function getUserLimitations()
    {
        return $this->membership->get_user_limitations(request());
    }
}
