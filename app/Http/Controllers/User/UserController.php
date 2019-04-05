<?php

namespace App\Http\Controllers\User;

use App\Contracts\User\UserInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    //
    protected $user;
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function follow(Request $request) {
        return $this->user->followUser($request);
    }

    public function memberSearch(Request $request) {
        return $this->user->memberSearch($request);
    }

    public function getNewlyRegisteredCounter(Request $request) {
        return $this->user->getNewlyRegisteredCounter($request);
    }
}
