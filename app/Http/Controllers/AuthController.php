<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contracts\AuthInterface;

class AuthController extends Controller
{
    protected $auth;
    protected $request;

    public function __construct(AuthInterface $auth, Request $request)
    {
        $this->auth = $auth;
        $this->request = $request;
    }

    /**
     * @SWG\POST(
     *     path="/auth/login",
     *     tags={"LOGIN-API"},
     *     summary="Authenticate A Users",
     *     @SWG\Parameter(
     *      name="email", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="User Successfully Authenticated"),
     *     @SWG\Response(response=412, description="Validation errors"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
     */
    public function login()
    {
        return $this->auth->login($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/auth/register",
     *     tags={"REGISTRATION-API"},
     *     summary="Register Users",
     *     @SWG\Parameter(
     *      name="ref_code", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="name", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="username", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="email", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="User Successfully Registered"),
     *     @SWG\Response(response=412, description="Validation errors"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
     */
    public function register()
    {
        return $this->auth->register($this->request);
    }

    public function facebookRedirect() {

        return $this->auth->socialRedirect($this->request);
    }

    public function twitterRedirect() {

        return $this->auth->socialRedirect($this->request);
    }

    public function googleRedirect() {

        return $this->auth->socialRedirect($this->request);
    }

    public function linkedinRedirect() {

        return $this->auth->socialRedirect($this->request);
    }

    public function facebookCallback() {

        return $this->auth->faceBookCallback();
    }

    public function twitterCallback() {

        return $this->auth->twitterCallback();
    }

    public function googleCallback() {

        return $this->auth->googleCallback();
    }

    public function linkedinCallback() {

        return $this->auth->linkedinCallback();
    }

    public function requestDevice() {

        return $this->auth->requestDevice();
    }

    public function refresh_token() {
        return $this->auth->refresh_token();
    }


    /**
     * @SWG\POST(
     *     path="/auth/check-referral-code",
     *     tags={"REGISTRATION-API"},
     *     summary="Check Referral Code",
     *     @SWG\Parameter(
     *      name="code", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="Referral code is valid!"),
     *     @SWG\Response(response=412, description="Referral code is not valid!"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
     */
    public function checkReferralCode() {

        return $this->auth->checkReferralCode($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/auth/facebook-login",
     *     tags={"LOGIN-API"},
     *     summary="Facebook login",
     *     @SWG\Parameter(
     *      name="id", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully logged-in using facebook!"),
     *     @SWG\Response(response=412, description="Failed to login using facebook!"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
    */
    public function facebookLogin() {

        return $this->auth->facebookLogin($this->request);
    }
    /**
     * @SWG\POST(
     *     path="/auth/facebook-register",
     *     tags={"LOGIN-API"},
     *     summary="Facebook login",
     *     @SWG\Parameter(
     *      name="id", in="formData", required=true, type="string"
     *      ),
     *      @SWG\Parameter(
     *      name="email", in="formData", required=true, type="string"
     *      ),
     *      @SWG\Parameter(
     *      name="name", in="formData", required=true, type="string"
     *      ),
     *      @SWG\Parameter(
     *          name="ref_code", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successful signup using facebook!"),
     *     @SWG\Response(response=412, description="Failed to signup using facebook!"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
    */
    public function registerViaFacebook(){
        return $this->auth->registerViaFacebook($this->request);
    }
    /**
     * @SWG\POST(
     *     path="/auth/verify-email",
     *     tags={"LOGIN-API"},
     *     summary="Verify Email",
     *     @SWG\Parameter(
     *      name="token", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully verified email!"),
     *     @SWG\Response(response=412, description="Failed to verify email!"),
     *     @SWG\Response(response=500, description="Internal error")
     * )
    */
    public function verifyEmail($token) {

        return $this->auth->verifyEmail($token);
    }

    public function checkEmail() {

        return $this->auth->checkEmail($this->request);
    }

    public function checkUsername($username) {

        return $this->auth->checkUsername($username);
    }

    public function forgotPassword() {

        return $this->auth->forgotPassword($this->request);
    }

    public function resetPassword() {

        return $this->auth->resetPassword($this->request);
    }

    public function postValidateToken() {

        return $this->auth->postValidateToken($this->request);
    }

    public function socialConnect(){
        return $this->auth->socialConnect($this->request);
    }

    public function saveReferrer(){
        return $this->auth->saveReferrer($this->request);
    }
}
