<?php

namespace App\Contracts\Manager;

interface ReferralInterface
{

    public function index();
    
    public function setReferralSettings($request);

    public function taskPointSettingsHistory($request);

    public function signupRewardSettingsHistory($request);
}