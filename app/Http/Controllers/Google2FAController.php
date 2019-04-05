<?php

namespace App\Http\Controllers;

use App\Model\UserGoogleAuth;
use Illuminate\Http\Request;
use \ParagonIE\ConstantTime\Base32;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use App\Traits\UtilityTrait;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use App\User;
use Carbon\Carbon;
use DB;

class Google2FAController extends Controller
{
    use UtilityTrait;

     /**
     * @SWG\POST(
     *     path="/social/2fa/enable",
     *     tags={"PROFILE-API"},
     *     summary="2FA Enable",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully enabled Google 2fa!"),
     *     @SWG\Response(response=401, description="Failed to enable google 2fa!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function enableTwoFactor(Request $request)
    {

        //generate new secret
        $secret = $this->generateSecret();
        //get user
        $user_2fa = new UserGoogleAuth;

        $user_id = $request->user_id;

        $user = User::find($user_id);
        
        $user_2fa_update = UserGoogleAuth::where('user_id',$user->id)->first();

        
        //encrypt and then save secret
        if (!empty($user_2fa_update)) {
            $user_2fa_update->user_id = $user->id;
            $user_2fa_update->google2fa_secret = Crypt::encrypt($secret);
            $user_2fa_update->secret_key = $secret;
            $is_verified = $user_2fa_update->verified;
            if($is_verified == null){
               $user_2fa_update->save();
            }
        }else{
            $user_2fa->user_id = $user->id;
            $user_2fa->google2fa_secret = Crypt::encrypt($secret);
            $user_2fa->secret_key = $secret;
            $user_2fa->save();
            $is_verified = null;
        }

        //generate image for QR barcode
        $imageDataUri = (new Google2FA())->getQRCodeInline(
            $request->getHttpHost(),
            $user->email,
            $secret,
            200
        );

        $username = $user->username;

        $data = [
            'image' => $imageDataUri,
            'secret' => $secret,
            'username'=> $username,
            'verified' => $is_verified
        ];

        if(count($data) > 0){
            return static::response(null,static::responseJwtEncoder($data), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }   

     /**
     * @SWG\POST(
     *     path="/social/2fa/disable",
     *     tags={"PROFILE-API"},
     *     summary="2FA Disable",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="totp", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully disabled Google 2fa!"),
     *     @SWG\Response(response=401, description="Failed to disable google 2fa!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function disableTwoFactor(Request $request)
    {
        $user_id = $request->user_id;
        $totp = $request->totp;

        $this->validate($request,[
            'totp' => 'bail|required|digits:6'
        ]);

        $key = $user_id . ':' . $totp;

        $user = User::find($user_id);
        $user_2fa_update = UserGoogleAuth::where('user_id',$user->id)->first();     
          
        $google2fa_secret = Crypt::decrypt($user_2fa_update->google2fa_secret);
        $validateToken = (new Google2FA())->verifyKey($google2fa_secret, $totp);

        if($validateToken == false){
            return static::response('Not a valid token!',null, 400, 'error');
        }

        if(Cache::has($key)){
            return static::response('Cannot reuse token!',null, 400, 'error');
        }

        //make secret column blank
        $user_2fa_update->verified = null;
        if($user_2fa_update->save() )
        {
          $user->google2fa_secret = null;
          $user->save();
          Cache::add($key, true, 4);
          return static::response('Google 2FA has been disabled',null, 200, 'success');
        }
    }

     /**
     * @SWG\POST(
     *     path="/social/2fa/register",
     *     tags={"PROFILE-API"},
     *     summary="2FA Register",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="totp", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="password", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="secret", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully registered google 2fa!"),
     *     @SWG\Response(response=401, description="Failed to register google 2fa!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function registerTwoFactor(Request $request)
    {
        $user_id = $request->user_id;
        $totp = $request->totp;
        $password = $request->password;
        $secret = $request->secret;

        $this->validate($request,[
            'totp' => 'bail|required|digits:6'
        ]);


        $user = User::find($user_id);
        $key = $user_id . ':' . $totp;
        $user_2fa_update = UserGoogleAuth::where('user_id', $user->id)->first();

        $google2fa_secret = Crypt::decrypt($user_2fa_update->google2fa_secret);
        $validateToken = (new Google2FA())->verifyKey($google2fa_secret, $totp);

        if($validateToken == false){
            return static::response('Not a valid token!',null, 400, 'error');
        }

        if(Cache::has($key)){
            return static::response('Cannot reuse token!',null, 400, 'error');
        }

        if (!Hash::check($password, $user->password)){
                return static::response('Incorrect Password',null, 400, 'error');
        }elseif ($secret != null){
            if ($secret != $user_2fa_update->secret_key){
                    return static::response('Incorrect Secret Key',null, 400, 'error');
            }elseif ($secret == $user_2fa_update->secret_key && Hash::check($password, $user->password)){
                $user_2fa_update->verified = 1;
                if ($user_2fa_update->save()){
                    $user->google2fa_secret = $user_2fa_update->google2fa_secret;
                    $user->save();
                    Cache::add($key, true, 4);
                    return static::response('Google 2FA has been enable',null, 200, 'success');
                }
            }
        }else{
            $user_2fa_update->verified = 1;
            if ($user_2fa_update->save()){
                $user->google2fa_secret = $user_2fa_update->google2fa_secret;
                $user->save();
                return static::response('Google 2FA has been enable',null, 200, 'success');
            }
        }
    }

    /**
     * Generate a secret key in Base32 format
     *
     * @return string
     */
    private function generateSecret()
    {
        $randomBytes = random_bytes(10);

        return Base32::encodeUpper($randomBytes);
    }

    
    /**
     * @SWG\POST(
     *     path="/social/2fa/list",
     *     tags={"ADMIN-API"},
     *     summary="2FA Reset List",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded 2fa list!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function twoFaList(Request $request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = "";
        if($request->has('filter_date')){
            if($request->filter_date <> ''){
                $filter_date = Carbon::parse($request->filter_date)->toDateString();
            }
        }
        
        $query = User::select(['users.id as user_id', 
                                DB::raw('IFNULL(g.created_at,users.created_at) AS date_created'), 
                                'users.name', 'users.email', 'users.username'])
                    ->leftJoin('user_google_auth AS g','g.user_id','=','users.id')
                    ->orWhere('users.google2fa_secret','<>','');
                   

        $count = $query->count();

        if($filter_date <> ''){
            $query->whereDate(DB::raw('IFNULL(g.created_at,users.created_at)'),'=',$filter_date);
        }

        if($search_key <> ''){
            $query->where('users.name','LIKE','%'.$search_key.'%')
                  ->orWhere('users.username','LIKE','%'.$search_key.'%')
                  ->orWhere('users.email','LIKE','%'.$search_key.'%');
        }

        $twofa = $query->orderByDesc(DB::raw('IFNULL(g.created_at,users.created_at)'))->offset($offset)->limit($limit)->get();
        $data = [];
        $list = [];

        if($count > 0){
            foreach($twofa as $key => $value){
                $item = [
                    'username' => $value->username,
                    'name' => $value->name,
                    'email' => $value->email,
                    'status' => 'enable',
                    'date' => $value->date_created,
                ];

                $data[] = $item;
            }

            $list['list'] = $data;
            $list['total_count'] = $count;

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }

        return static::response('No Data Fetched!',null, 400, 'failed');
    }

    /**
     * @SWG\POST(
     *     path="/social/2fa/reset",
     *     tags={"ADMIN-API"},
     *     summary="2FA Reset",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully reset google 2FA!"),
     *     @SWG\Response(response=401, description="Failed to reset google 2FA!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function twoFaReset(Request $request){
        $user_id = $request->user_id;
        $now = date('Y-m-d H:i:s');
        if($user_id <> ''){
            $google = UserGoogleAuth::where('user_id',$user_id)->first();
            if($google <> null){
                $model = UserGoogleAuth::find($google->id);
                if($model->delete()){
                    $msg = "Reset 2FA [{$google->id}]=>[user_id]->{$user_id}, [reset_at]->{$now}";
                    record_admin_activity(Auth::id(), 2, $msg, 'social', 1, '2fa Reset', $user_id);
                }
            }

            $user_check = User::where('id',$user_id)->first();
            if($user_check){
                $user = User::find($user_check->id);
                $user->google2fa_secret = null;
                if($user->save()){
                    return static::response('Successfully reset google 2fa!',null, 200, 'success');
                }
            }
        }
        return static::response('Failed to reset google 2fa!',null, 400, 'failed');
    }
}
