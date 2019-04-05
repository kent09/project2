<?php

namespace App\Contracts;

interface LeaderBoardInterface
{
    public function referral($request);

    public function general($request);

    public function own($request);
}