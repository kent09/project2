<?php

namespace App\Contracts;

interface AuthInterface
{
    public function login($request);

    public function register($req);

    public function socialRedirect($request);

    public function faceBookCallback();

    public function googleCallback();

    public function linkedinCallback();

    public function twitterCallback();

    public function requestDevice();

    public function refresh_token();

    public function checkReferralCode($request);

    public function facebookLogin($request);

    public function registerViaFacebook($request);

    public function checkEmail($request);

    public function checkUsername($username);

    public function forgotPassword($request);

    public function resetPassword($request);

    public function postValidateToken($request);

    public function socialConnect($request);
    public function saveReferrer($request);
}