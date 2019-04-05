<?php

namespace App\Repository;


use App\User;
use App\Session;
use App\Model\Fund;
use App\Jobs\SaveReferral;
use App\Mail\ResetPassword;
use App\Traits\WizardTrait;
use Jenssegers\Agent\Agent;
use App\Model\SocialConnect;
use App\Model\UserGoogleAuth;
use App\Jobs\UpdateUserCookie;
use App\Mail\EmailVerification;
use App\Model\PushNotification;
use App\Contracts\AuthInterface;
use App\Traits\Manager\UserTrait;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonWebTokenWrapper;
use LaravelHashids\Facades\Hashids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Auth\Events\Registered;
use App\Mail\SendNotificationToReferrer;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;

class AuthRepository implements AuthInterface
{
    use JsonWebTokenWrapper, WizardTrait, UserTrait;


    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function login($request)
    {
        $error_messages = [];

        DB::beginTransaction();

        try {

            $user = User::where('email', $request->email)->first([
                'id','name','password','username','email','type','verified','status','agreed','ban'
            ]);
           
            if ( !$user )
                $error_messages[] = 'Invalid Email Address or Invalid Password';

            if( $user ) {
                if ( !static::hash_check($request->password, $user->password) )
                    $error_messages[] = 'Invalid Email Address or Invalid Password';
            }

            if( $user ) {
                if( static::hash_check($request->password, $user->password) ) {

                    if($user->ban > 0){
                        if($user->ban == 1){
                            $error_messages[] = 'Account is Soft-Banned';
                        }else if($user->ban == 2){
                            $error_messages[] = 'Account is Hard-Banned';
                        }
                    }else{
                        if($user->status == 0){
                            if($user->verified == 1){
                                if($user->agreed == 1){
                                    $error_messages[] = 'Account is Disabled';
                                }
                            }
                        }
                    }

                    // if ($user->verified == 0)
                    //     $error_messages[] = 'Account Not Verified';

                    // if ($user->status == 0)
                    //     $error_messages[] = 'Account Not Activated';

                    // if ($user->ban == 1)
                    //     $error_messages[] = 'Account is Soft-Banned';

                    // if ($user->ban == 2)
                    //     $error_messages[] = 'Account is Hard-Banned';

                }
            }

            if( count($error_messages) > 0 ) {
                DB::rollBack();
                return static::response('Error', $error_messages, 401, 'error');
            }

            if( $user ) {
                $session = Session::has('session_detail')->where('user_id', $user->id)->first();
            
                $token = static::jwtEncodeWrapper(
                    [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'verified' => $user->verified,
                        'ban' => $user->ban,
                        'agreed' => $user->agreed,
                        'username' => $user->username,
                        'type' => $user->type,
                        'device' => ( new Agent() )->isDesktop() ? 'true' : 'false',
                        'membership' => static::userMembership($user->id),
                        'limitations' => static::userAccessLimitations($user->id)
                    ], 7);
            
                if (!$session OR $session->status == 0) {
                    $id = hash('md5', app('carbon')->now() );
                    (new Session)->saveData([
                        'id' => $id,
                        'user_id' => $user->id,
                        'ip_address' => $request->server('REMOTE_ADDR'),
                        'user_agent' => $request->server('HTTP_USER_AGENT'),
                        'payload' => $token,
                        'last_activity' => app('carbon')->now()->timestamp,
                        'login_date' => date('Y-m-d h:m:s')
                    ]);
                }
                
                $check_2fa = UserGoogleAuth::where('user_id',$user->id)->where('verified',1)->first();
                $enable2fa = false;
                if($check_2fa <> null){
                    $enable2fa = true;
                }
                
                DB::commit();
                return static::response('Successful Authentication', ['token' => $token, 'enable2fa' => $enable2fa], 200, 'success');
            }

        } catch (\Exception $e) {
            $error_messages[] = 'Error, Something went wrong, please try again!';

            DB::rollBack();
            return static::response('Error', $error_messages, 500, 'error');
        }
    }

    public function socialRedirect($request) {
        switch($request->social) {
            case 'facebook':
                return Socialite::with($request->social)->stateless()->redirect();
            break;
            case 'twitter':
                return Socialite::with($request->social)->stateless()->redirect();
            break;
            case 'google':
                return Socialite::with($request->social)->stateless()->redirect();
            break;
            case 'linkedin':
                return Socialite::with($request->social)->stateless()->redirect();
            break;
            default:
                return 'Error, un-recognised social!';
            break;
        }
    }

    #callback
    public function faceBookCallback() {
        $callback = static::socialConnectCallback('facebook');
        if( $callback )
            return static::response("Welcome! You've successfully Connected your Facebook Account.", null, 200, "success");

        if( $callback === 3 )
            return static::response('Account Already Taken', null, 409, "error");

        return static::response('Something went wrong while connecting social account', null, 400, 'error');
    }

    public function googleCallback() {
        $callback = static::socialConnectCallback('google');
        if( $callback )
            return static::response("Welcome! You've successfully Connected your Google Account.", null, 200, "success");

        if( $callback === 3 )
            return static::response('Account Already Taken', null, 409, "error");

        return static::response('Something went wrong while connecting social account', null, 400, 'error');
    }

    public function linkedinCallback() {
        $callback = static::socialConnectCallback('linkedin');
        if( $callback )
            return static::response("Welcome! You've successfully Connected your Linkedin Account.", null, 200, "success");

        if( $callback === 3 )
            return static::response('Account Already Taken', null, 409, "error");

        return static::response('Something went wrong while connecting social account', null, 400, 'error');
    }

    public function twitterCallback() {
        $callback = static::socialConnectCallback('twitter');
        if( $callback )
            return static::response("Welcome! You've successfully Connected your Twitter Account.", null, 200, "success");

        if( $callback === 3 )
            return static::response('Account Already Taken', null, 409, "error");

        return static::response('Something went wrong while connecting social account', null, 400, 'error');
    }


    public function register($request)
    {
        $ref_code = $request->has('ref_code') ? $request->ref_code : '';
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        event(new Registered($user = $this->create($request->all())));

        if(is_object($user)==false){
            return static::response($user, null, 400, "failed");
        }
        $new_user = User::find($user->id);
        if ($new_user){
            $new_user->ref_code = static::get_referral_code($new_user->id);
            $new_user->save();
        }
        $email = new EmailVerification($user);
        Mail::to($user->email)->send($email);

        dispatch(new UpdateUserCookie($user->id, 1));

        if ($ref_code != null){
            $referrer = User::where('ref_code', $ref_code)->where('status', 1)->where('verified', 1)->where('agreed', 1)->first();
            if ($referrer != null) {
                if ($referrer->ban < 2) {
                    $new_user->referrer_id = $referrer->id;
                    $new_user->save();

                    // dispatch(new SaveReferral($user, $ref_code));
                    static::create_referral([
                        'user_id' => $new_user->id,
                        'referrer_id' => $referrer->id,
                    ]);
                    $email_notification = PushNotification::where('user_id', $user->id)->first();
                    if ($email_notification != null){
                        if (isset($email_notification->options)){
                            $get_email_settings = json_decode($email_notification->options, true);
                            if (array_key_exists("email", $get_email_settings)) {
                                if ($get_email_settings['email'] == true) {
                                    $email = new SendNotificationToReferrer($user, $referrer);
                                    Mail::to($referrer->email)->send($email);
                                }
                            }
                        }
                    }else{
                        $email = new SendNotificationToReferrer($user, $referrer);
                        Mail::to($referrer->email)->send($email);
                    }
                }
            }
        }
        return static::response('Successfully Registered. Please check your Email.', null, 201, "success");

    }

    /*
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $fund = Fund::where('email','=', $data['email'])->first();
        $balance = 0;
        if($fund){
            $balance = $fund->balance;
        }

        $args = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => b_crypt($data['password']),
            'email_token' => static::generateRandomString(),
            'balance' => $balance,
            'ip' => $ip
        ];


    $user = static::create_user($args)['data'];
        return $user;
    }


    /*
     * Check referral code if valid
     *
     * @param  array  $request
     * @return array
     */
    public function checkReferralCode($request)
    {
        $code = strtoupper($request->code);
        $user = User::where('ref_code', $code)->where('status', 1)->where('verified', 1)->where('agreed', 1)->first();
        if ($user==null){
            return static::response('Code Not Found.', null, 400, "error");
        }
        if ($user->ban == 1){
            return static::response('This User is SOFT-BANNED.', null, 400, "error");
        }
        if ($user->ban == 2) {
            return static::response('This User is HARD-BANNED.', null, 400, "error");
        }

        $data = [
            'referrer_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username
        ];

        return static::response('',static::responseJwtEncoder($data), 200, "success");
    }

    public function registerViaFacebook($request){
        $socialfb = SocialConnect::where('account_id',$request->id)
            ->where('status','!=',2)
            ->first();

        if(!$socialfb){
            $ref_code = $request->has('ref_code') ? $request->ref_code : '';
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'username' => 'required|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            event(new Registered($user = $this->create($request->all())));

            if(is_object($user)==false){
                return static::response($user, null, 400, "failed");
            }

            if ($ref_code != null){
                $referrer = User::where('ref_code', $ref_code)->where('status', 1)->where('verified', 1)->where('agreed', 1)->first();
                if ($referrer != null) {
                    if ($referrer->ban < 2) {
                        $user->referrer_id = $referrer->id;
                        $user->save();

                        // dispatch(new SaveReferral($user, $ref_code));
                        static::create_referral([
                            'user_id' => $user->id,
                            'referrer_id' => $referrer->id,
                        ]);
                        $email_notification = PushNotification::where('user_id', $user->id)->first();
                        if ($email_notification != null){
                            if (isset($email_notification->options)){
                                $get_email_settings = json_decode($email_notification->options, true);
                                if (array_key_exists("email", $get_email_settings)) {
                                    if ($get_email_settings['email'] == true) {
                                        $email = new SendNotificationToReferrer($user, $referrer);
                                        Mail::to($referrer->email)->send($email);
                                    }
                                }
                            }
                        }else{
                                        $email = new SendNotificationToReferrer($user, $referrer);
                                        Mail::to($referrer->email)->send($email);
                        }
                    }
                }
            }

            $new_user = User::find($user->id);
            if ($new_user){
                $new_user->ref_code = static::get_referral_code($new_user->id);
                $new_user->status = 1;
                $new_user->agreed = 1;
                $new_user->verified = 1;
                $new_user->save();
            }
            $socialConnect = new SocialConnect();
            $socialConnect->user_id = $new_user->id;
            $socialConnect->social = 'facebook';
            $socialConnect->account_name = $new_user->name;
            $socialConnect->account_id = $request->id;
            if(!$socialConnect->save()){
                return static::response('Failed linking social connect.', null, 401, "success");
            }
            // $email = new EmailVerification($user);
            // Mail::to($user->email)->send($email);

            dispatch(new UpdateUserCookie($user->id, 1));
            $token = static::jwtEncodeWrapper(
                [
                    'user_id' => $new_user->id,
                    'email' => $new_user->email,
                    'name' => $new_user->name,
                    'username' => $new_user->username,
                    'type' => $new_user->type,
                    'verified' => $new_user->verified,
                    'ban' => $new_user->ban,
                    'agreed' => $new_user->agreed,
                    'device' => ( new Agent() )->isDesktop() ? 'true' : 'false'
                ], 7);
            return static::response('Successfully Registered.', $token, 200, "success");
        }
    }

    public function facebookLogin($request){
        $socialfb = SocialConnect::where('account_id',$request->id)
            ->where('status',1)
            ->first();
           
        if($socialfb){
            $user = User::where('id', $socialfb->user_id)->first([
                'id','name','password','username','email','type','verified','status','agreed','ban'
            ]);

          
            $session = Session::has('session_detail')->where('user_id', $user->id)->first();

            $token = static::jwtEncodeWrapper(
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'username' => $user->username,
                    'type' => $user->type,
                    'verified' => $user->verified,
                    'ban' => $user->ban,
                    'agreed' => $user->agreed,
                    'device' => ( new Agent() )->isDesktop() ? 'true' : 'false'
                ], 7);

          
                // unset($user->verified);
                // unset($user->status);
                // unset($user->agreed);
                // unset($user->ban);

            if (!$session OR $session->status == 0) {
                $id = hash('md5', app('carbon')->now() );
                (new Session)->saveData([
                    'id' => $id,
                    'user_id' => $user->id,
                    'ip_address' => $request->server('REMOTE_ADDR'),
                    'user_agent' => $request->server('HTTP_USER_AGENT'),
                    'payload' => $token,
                    'last_activity' => app('carbon')->now()->timestamp,
                    'login_date' => date('Y-m-d h:m:s')
                ]);
            }

            return static::response('Successful Authentication', $token, 200, 'success');
        }
        return static::response('Unable to fetch user information', null, 402, 'error');
    }

    public function requestDevice() {
        return ( new Agent() )->isDesktop() ? 'true' : 'false';
    }

    public function refresh_token()
    {
        $user = User::find(Auth::id());
        $session = Session::has('session_detail')->where('user_id', $user->id)->first();
        
        $token = static::jwtEncodeWrapper(
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'verified' => $user->verified,
                'ban' => $user->ban,
                'agreed' => $user->agreed,
                'username' => $user->username,
                'type' => $user->type,
                'device' => ( new Agent() )->isDesktop() ? 'true' : 'false',
                'membership' => static::userMembership($user->id),
                'limitations' => static::userAccessLimitations($user->id)
            ], 7);

        if (!$session OR $session->status == 0) {
            $id = hash('md5', app('carbon')->now() );
            (new Session)->saveData([
                'id' => $id,
                'user_id' => $user->id,
                'ip_address' => $request->server('REMOTE_ADDR'),
                'user_agent' => $request->server('HTTP_USER_AGENT'),
                'payload' => $token,
                'last_activity' => app('carbon')->now()->timestamp,
                'login_date' => date('Y-m-d h:m:s')
            ]);
        }

        return static::response('Successful Token Refreshed', $token, 200, 'success');
    }

    public function verifyEmail($token) {
        $user = User::where('email_token',$token)->first();
        if($user){
            $user->verified = 1;
            if($user->save()){
                return error(200, 'Successfully verified email!');
             }
        }
        return error(400, 'Failed to verify email');
    }

    public function checkEmail($request) {
        $email = $request->email;

        if($email <> ''){
            $checker = User::where('email',$email)->count();
            if($checker > 0){
                return static::response('Email is already used!', null, 400, "error");
            }else{
                return static::response(null, null, 200, "success");
            }
        }
        return static::response('Email address is not specified!', null, 400, "error");
    }

    public function checkUsername($username='') {
        if($username <> ''){
            $checker = User::where('username',$username)->count();
            if($checker > 0){
                return static::response('Username is already used!', null, 400, "error");
            }else{
                return static::response(null, null, 200, "success");
            }
        }
        return static::response('Username is not specified!', null, 400, "error");

    }

    public function forgotPassword($request){
        $email = $request->email;

        // $user_check = User::where('email', $email)->first();
        $user_check = DB::table('users')->where('email',$email)->first();

        if($user_check){

            if (($user_check->status == 0) || ($user_check->ban == 1)) {
                return static::response('Your account is not activated. Please activate it first.', null, 400, "error");
            }

            if($user_check->verified == 0){
                return static::response('Your account is not yet verified. Please verify your email first.', null, 400, "error");
            }

            $token = Hashids::encode(app('carbon')->now()->timestamp);
            $save_token = DB::table('users')->where('email',$email)->update(['password_reset_token' => $token]);
            if($save_token){
                $user_checks = DB::table('users')->where('email',$email)->first();
                $reset_pass = new ResetPassword($user_checks);
                Mail::to($email)->send($reset_pass);
                $data = array('name'=>"Sam Jose", "body" => "Test mail");
                return static::response('Email for password reset request was successfully sent!', null, 200, "success");
            }else{
                return static::response('Failed to generate password reset token!', null, 400, "error");
            }
        }   

        return static::response('Email address does not exist in the database!', null, 400, "error");
        
    }

    public function resetPassword($request){
        $token = $request->token;
        $email = $request->email;
        $password = $request->password;

        if($token == ""){
            return static::response('Invalid reset password token!', null, 400, "error");
        }

        if($email == ""){
            return static::response('Email address is required!', null, 400, "error");
        }

        if($password == ""){
            return static::response('Password is required!', null, 400, "error");
        }

        $checker = User::where('password_reset_token',$token)->where('email',$email)->first();
        if($checker == null){
            return static::response('Failed to reset password.. Token is invalid!', null, 400, "error");
        }else{
            $check_email = User::where('email',$email)->first();
            if($check_email){
                $checker->password = b_crypt($password);
                $checker->password_reset_token = null;
                if($checker->save()){
                    return static::response('Successfully reset password!', null, 200, "success");
                }
            }else{
                return static::response('Email address does not exist in the database!', null, 400, "error");
            }
        }
    }  
    
    public function postValidateToken($request){
        $user_id = $request->user_id;
        $totp = $request->totp;

        $user = User::find($user_id);
        if($user){
            $user_2fa_update = UserGoogleAuth::where('user_id', $user->id)->first();
            if($user_2fa_update){
                $google2fa_secret = Crypt::decrypt($user_2fa_update->google2fa_secret);
                $validateToken = (new Google2FA())->verifyKey($google2fa_secret, $totp);

                if($validateToken == false){
                    return static::response('Not a valid token!',null, 400, 'error');
                }
                
                $key = $user_id . ':' . $totp;

                if(Cache::has($key)){
                    return static::response('Cannot reuse token!',null, 400, 'error');
                }else{
                    Cache::add($key, true, 4);
                    return static::response('Successfully validated token!',null, 200, 'success');
                }
            }
        }
        
        return static::response('Failed to validate token!',null, 400, 'error');
    }

    public function socialConnect($request){
        $social = SocialConnect::where('social',$request->social)
            ->where('account_id', $request->account_id)
            ->where('status','<>',2)
            ->first();
        
        if($social){
            return static::response('Social Account already link to another user',null, 401, 'error');
        }
        $user = User::find($request->user_id);

        $new_social = new SocialConnect;
        $new_social->user_id = $request->user_id;
        $new_social->social = $request->social;
        $new_social->account_name = $request->account_name;
        $new_social->account_id = $request->account_id;
        if($new_social->save()){
            $user->agreed = 1;
            $user->save();
            
            $token = static::jwtEncodeWrapper(
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'verified' => $user->verified,
                    'ban' => $user->ban,
                    'agreed' => $user->agreed,
                    'username' => $user->username,
                    'type' => $user->type,
                    'device' => ( new Agent() )->isDesktop() ? 'true' : 'false'
                ], 7);

            return static::response($request->social .' account successfuly linked.',$token,200, 'success');
        }

        return static::response('Failed to link social account. Please try again',null, 500, 'error');
    }

    public function saveReferrer($request){

        $user = User::find($request->id);
        
        $referrer = User::where('id', $request->referrer_id)
                ->where('status', 1)
                ->where('verified', 1)
                ->where('agreed', 1)
                ->first();


            if ($referrer != null) {
                if ($referrer->ban < 2) {
                    $user->referrer_id = $referrer->id;
                    $user->save();

                    static::create_referral([
                        'user_id' => $user->id,
                        'referrer_id' => $referrer->id,
                    ]);
                    
                    // dispatch(new SaveReferral($user, $referrer->ref_code));
                  
                    $email_notification = PushNotification::where('user_id', $user->id)->first();
                    if ($email_notification != null){
                        if (isset($email_notification->options)){
                            $get_email_settings = json_decode($email_notification->options, true);
                            if (array_key_exists("email", $get_email_settings)) {
                                if ($get_email_settings['email'] == true) {
                                    $email = new SendNotificationToReferrer($user, $referrer);
                                    Mail::to($referrer->email)->send($email);
                                }
                            }
                        }
                    }else{
                                    $email = new SendNotificationToReferrer($user, $referrer);
                                    Mail::to($referrer->email)->send($email);
                    }

                    return static::response('Successfull',null,200,'success');
                }
            }
            else {
                return static::response('Referral code not found or invalid',null,404,'error');
            }
    }
}