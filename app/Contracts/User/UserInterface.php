<?php

namespace App\Contracts\User;


interface UserInterface
{
    public function followUser($request);

    public function memberSearch($request);

    public function getNewlyRegisteredCounter($request);
}