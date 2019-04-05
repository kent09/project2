<?php

namespace App\Repository\Profile;

use App\Model\Task;
use App\User;
use Hash;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Storage;
use App\Model\Verified;
use App\Model\BlockedUser;
use App\Model\UserFollower;
use App\Model\UserActivity;
use App\Model\SocialConnect;
use App\Model\DbWithdrawal;
use App\Model\SocialConnectHistory;
use App\Model\GiftCoinTransaction;
use App\Model\BlockUserTask;
use App\Helpers\UtilHelper;
use App\Traits\TaskTrait;
use App\Traits\UtilityTrait;
use App\Traits\Manager\UserTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Profile\ProfileInterface;
use App\Repository\WalletRepository;
use Carbon\Carbon;
use App\Events\NewGiftCoin;
use App\Classes\CountryList;
use App\Events\NewFollowNotification;
use App\Events\NewUnfollowNotification;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SaveReferral;

class ProfileRepository implements ProfileInterface
{
    use UtilityTrait, UserTrait, TaskTrait;

    protected $limit = 10;

    /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profileMainInfo($request){
//        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else{
            if(is_numeric($request->username)){
                $user = User::find($request->username);
                if($user){
                    $user_id = $user->id;
                }else{
                    $user_id =  Auth::id();
                }
            }else{
                $user_id =  Auth::id();
            }
        } 

        $user = static::get_user($user_id);

        $country = new CountryList;
        $country = $country->country();

        $profile = [];
        if($user){
            $user_info = $user;

            $profile = [
              'user_id' => $user->id,
              'name' => $user_info->name,
              'email' => $user_info->email,
              'username' => $user_info->username,
              'ref_code' => $user_info->ref_code,
              'location' => $user_info->location,
              'country' => $user_info->country,
              'about' => $user_info->about, 
              'join_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$user_info->created_at)->toDateTimeString(),
              'countryList' => $country,
              'is_follower' => static::is_follower(['follower_id' => $user_id, 'followed_id' => Auth::id()]),
              'membership' => static::userMembership($user->id),

            ];
            if ($profile){
                return static::response(null,static::responseJwtEncoder($profile), 201, 'success');
            }
        }
        return static::response('No Data Fetched',null, 400, 'error');
    }

    
     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileImage($user_id){
        $url = env('PROFILE_IMAGE').$user_id.'/avatar.png';
        $headers = get_headers($url);
        $checker = stripos($headers[0],"200 OK")? true : false;
        
        if($checker){
            $avatar = env('PROFILE_IMAGE').$user_id.'/avatar.png';
        }else{
            $avatar = 'https://kimg.io/image/user-avatar-default.png';
        }

        return static::response(null,$avatar, 201, 'success');
    }

     /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countFollowers($request){
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else $user_id =  Auth::id();

        $user = static::get_user($user_id);

        if($user){

            $followers = static::count_followers($user_id);
           
            return static::response(null,$followers, 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }

     /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countFollowing($request){
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else $user_id =  Auth::id();

        $user = static::get_user($user_id);

        if($user){
            $following = static::count_following($user_id);
            return static::response(null,$following, 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }  

     /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countConnections($request){
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else $user_id =  Auth::id();

        $user = static::get_user($user_id);

        if($user){
            $connection = static::count_connections($user_id);
            return static::response(null,$connection, 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }

     /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReputationScore($request){
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else $user_id =  Auth::id();

        $user = static::get_user($user_id);

        if($user){
            $reputation = static::get_reputation_score($user_id);
            $reputation_score = $reputation['reputation'] ? $reputation['reputation'] : 0;
            return static::response(null,$reputation_score, 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }
    
    
     /**
     * @param $request [username]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityScore($request){
        $user = User::where('username', $request->username)->first();

        if($user){
            $user_id = $user['id'];
        }
        else $user_id =  Auth::id();

        $user = static::get_user($user_id);

        if($user){
            $reputation = static::get_reputation_score($user_id);
            $activity_score = $reputation['activity_score'] ? $reputation['activity_score'] : 0;
            return static::response(null,$activity_score, 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }
    

    /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSocialConnected($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $socials = [];
        $social = static::get_linked_social_connections($user_id);

        if($social['status'] == 200){
            foreach($social['data'] as $key => $con){
                $item = [
                    'social' => $con->social,
                    'account_name' => $con->account_name,
                    'account_id' => $con->account_id
                ];

                array_push($socials, $item);
            }
            return static::response(null,static::responseJwtEncoder($socials), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }

    /**
     * @param $request [user_id, status]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSocialConnectHistory($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $status = $request->has('status') ? $request->status : "1";
        $data = [];
        $history = [];
        $con_status = "";

        if($status == 1){ # linked/soft unlinked
            $social_status = ['1','2'];
            $history = SocialConnectHistory::where('user_id',$user_id)->where('hard_unlink_status',0)->whereIn('status',$social_status)->orderByDesc('created_at')->get();
        }else if($status == 2){ # hard unlinked
            $social_status = ['1','3'];
            $history = SocialConnectHistory::where('user_id',$user_id)
                                            ->where(function($q){
                                                $q->where('status',1)->where('hard_unlink_status','<>',0);
                                            })->orWhere('status',3)->orderByDesc('created_at')->get();
        }
        if(count($history) > 0){
            foreach($history as $key => $con){
                if($status == 1){
                    $con_status = $con->socialConnectStatus();
                }else if($status == 2){
                    $con_status = $con->hardUnlinkStatus();
                }
                $item = [
                    'social' => $con->social->social,
                    'account_name' => $con->account_name,
                    'status' => $con_status,
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s',$con->created_at)->toDateTimeString(),
                    'disapproved_reason' => $con->disapproved_reason
                ];

                array_push($data, $item);
            }
            return static::response(null,static::responseJwtEncoder($data), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }

    /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSocialConnectionStatus($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $socials = [];
        $social = static::get_all_social_connections($user_id);

        if($social['status'] == 200){
            return static::response(null,static::responseJwtEncoder($social['data']), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }

     /**
     * @param $request [user_id][name][username][about][location][country]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAccount($request) {
        $user_id = Auth::id();
        $location = "";
        $country = "";

        if($request->has('location')){
            $location = $request->location;
        }

        if($request->has('country')){
            $country = $request->country;
        }

        $data = [
            'id' => $user_id,
            'name' => $request->name,
            'username' => snake_case($request->username),
            'about' =>$request->about,
            'location' => $location,
            'country' =>$country
        ];

        $user = static::update_basic($data);

        if ($user['status'] == 200) {
            record_activity($user_id, 'profile', 'Update Basic Profile Info', 'User', $user_id, 'success');
            return static::response(null,$user, 201, 'success');
        }
        record_activity($user_id, 'profile', 'Update Basic Profile Info', 'User',$user_id, 'error');
        return static::response('Failed to update Profile!', null, 200, 'success');
    }

     /**
     * @param $request [user_id][cur_password][new_password]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword($request) {
        $user_id = Auth::id();

        // CHECK CURRENT Password
        if (!static::is_same_password($request->cur_password, $user_id)) {
                record_activity($user_id, 'profile', 'Invalid Current Password', 'User',  $user_id, 'error');
            return static::response('Invalid Current Password',null, 400, 'failed');
        } elseif ($request->cur_password == $request->new_password) {
            return static::response('"Cannot use current password',null, 400, 'failed');
        }
        static::update_password($request->new_password, $user_id);

            record_activity($user_id, 'profile', 'Password Changed', 'User', $user_id, 'success');
        return static::response('Password Changed!',null, 201, 'success');

    }

      /**
     * @param $request [user_id][image]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileImage($request) {
        $user_id = Auth::id();
        // $img = $request->image;
         $image_format = $request->image_format;


        if( !in_array($image_format, ['jpg', 'jpeg', 'png', 'gif']) ){
            return static::response('Profile Image is not supported!',null, 401, 'error');
        }

        // $path = 'public/image/profiles/' . $user_id . '/';
        // $filename = 'avatar';
        // File::makeDirectory($path, $mode = 0777, true, true);
        // $png = (string)Image::make($img->getRealPath())->encode('png');

        // Image::make($png)->resize(300, 300)->save($path . $filename . '.png');

        //     record_activity($user_id, 'profile', 'Change Profile Image', 'User', $user_id, 'success');
        // return static::response('Profile Avatar Updated!',null, 201, 'success');

        if($request->image){
          $path = 'image/profiles/' . $user_id . '/';
            if (false === File::exists($path)) {
                 File::makeDirectory($path, $mode = 0777, true, true);
            }
          $filename = 'avatar.png';
          $data = substr($request->image, strpos($request->image, ',') + 1);
          $data = base64_decode($data);

          file_put_contents($path.$filename, $data);
        }
        record_activity($user_id, 'profile', 'Change Profile Image', 'User', $user_id, 'success');
        return static::response('Profile Avatar Updated!',null, 201, 'success');
    }

    /**
     * @param $request [user_id][paginate]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllFollowers($request) {
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }
        $followers = [];
        $list = [];

        $data = [
            'user_id' => $user_id,
            'offset' => $offset,
            'limit' => $limit,
            'search_key' => $search_key
        ];

        $follower = static::get_follower_list($data);

        if ($follower['count'] > 0) {
            foreach($follower['data'] as $key => $follow){
                $item = [
                    'user_id' => $follow->follower_id,
                    'name' => $follow->name,
                    'email' => $follow->email,
                    'username' => $follow->username,
                    'location' => $follow->location,
                    'country' => $follow->country,
                    'is_followed' => static::is_follower(['follower_id' => $user_id, 'followed_id' => $follow->follower_id])
                 ];

                 array_push($followers,$item);
            }
            $list['list'] = $followers;
            $list['count'] = $follower['count'];

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }

      /**
     * @param $request [user_id][paginate]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllFollowing($request) {
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $following_list = [];
        $list = [];
        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }

        $data = [
            'user_id' => $request->has('user_id') ? $request->user_id : Auth::id(),
            'offset' => $offset,
            'limit' => $limit,
            'search_key' => $search_key
        ];

        $following = static::get_following_list($data);

        if ($following['count'] > 0) {
            foreach($following['data'] as $key => $follow){
                $item = [
                    'user_id' => $follow->id,
                    'name' => $follow->name,
                    'email' => $follow->email,
                    'username' => $follow->username,
                    'location' => $follow->location,
                    'country' => $follow->country
                 ];

                 array_push($following_list,$item);
            }
            $list['list'] = $following_list;
            $list['count'] = $following['count'];

            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }

      /**
     * @param $request [user_id][paginate]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllConnections($request) {
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $connect_list = [];
        $list = [];
        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $user_id = $user->id;  
            }
        }
        $data = [
            'user_id' => $request->has('user_id') ? $request->user_id : Auth::id(),
            'limit' => $limit,
            'offset' => $offset,
            'search_key' => $search_key
        ];

        $connections = static::get_connections_list($data);

        if ($connections['count'] > 0) {
            foreach($connections['data'] as $key => $con){
                $item = [
                    'user_id' => $con->id,
                    'name' => $con->name,
                    'email' => $con->email,
                    'username' => $con->username,
                    'location' => $con->location,
                    'country' => $con->country
                 ];

                 array_push($connect_list,$item);
            }

            $list['list'] = $connect_list;
            $list['count'] = $connections['count'];
            
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }

     /**
     * @param $request [user_id][paginate]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBlockedUsers($request) {
        $paginate = $request->has('paginate') ? $request->paginate : 0;
        $blocked_list = [];
        $data = [
            'user_id' => $request->has('user_id') ? $request->user_id : Auth::id(),
            'paginate' => $paginate
        ];

        $blocked = static::get_blocked_users_list($data);

        if ($blocked['status'] == 200) {
            foreach($blocked['data'] as $key => $block){
                $item = [
                    'user_id' => $block->id,
                    'name' => $block->name,
                    'email' => $block->email,
                    'username' => $block->username,
                    'location' => $block->location,
                    'country' => $block->country
                 ];

                 array_push($blocked_list,$item);
            }
            return static::response(null,static::responseJwtEncoder($blocked_list), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'success');
    }


     /**
     * @param $request [username][paginate][sort]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimeline($request) {
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $sort = $request->has('sort') ? $request->sort : 'desc';
        $user = User::where('username', $request->username)->first();
        $data = [
            'user_id' => $user['id'],
            'offset' => $offset,
            'limit' => $limit,
            'sort' => $sort
        ];

        $timeline = static::get_timeline($data);
  
        if ($timeline['count'] > 0) {
            return static::response(null,static::responseJwtEncoder($timeline), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'failed');
    }
     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSteemitFooter($request) {
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $data = [];
        $socials = [];

        $user = static::get_user($user_id);
        $social = static::get_social_connections($user_id);
        if($social['status'] == 200){
            foreach($social['data'] as $key => $con){
                $item = [
                    'social' => $con->social,
                    'account_name' => $con->account_name,
                    'account_id' => $con->account_id
                ];
                array_push($socials, $item);
            }
        }

        $data = [
            'flag' => static::get_flag($user_id),
            'name' => $user->name,
            'username' => $user->username,
            'socials' => $socials

        ];

        if(count($data) > 0){
            return static::response(null,static::responseJwtEncoder($data), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }

    /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginHistory($request){
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $offset = $request->has('offset') ? $request->offset : 0;
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $data = [
            'user_id' => $user_id,
            'limit' => $limit, 
            'offset' => $offset
        ];
        $history_list = [];
        $list = [];
        $history = static::get_user_activity_list($data);

        $total_count = UserActivity::where('user_id', $user_id)->count();

        if($history['status'] == 200){
            foreach($history['data'] as $key => $hist){
                $item = [
                    'ip_address' => $hist->ip,
                    'action' => $hist->action,
                    'location' => $hist->location,
                    'model_used' => $hist->device,
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s', $hist->created_at)->toDateTimeString(),
                    'browser_information' => $hist->ua
                 ];

                 array_push($list,$item);
            }
            $history_list['list'] = $list;
            $history_list['total_count'] =  $total_count;
            return static::response(null,static::responseJwtEncoder($history_list), 200, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'failed');
    }

     /**
     * @param $request ['user_id','offset','search_key']
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLoginHistory($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $this->query = $request->has('search_key') ? $request->search_key : '';
        $history_list = [];

        $history = UserActivity::where('user_id', $user_id)
                              ->where(function($q){
                                    $q->orWhere('ip','LIKE','%'.$this->query.'%')
                                      ->orWhere('action','LIKE','%'.$this->query.'%')
                                      ->orWhere('location','LIKE','%'.$this->query.'%')
                                      ->orWhere('device','LIKE','%'.$this->query.'%')
                                      ->orWhere('ua','LIKE','%'.$this->query.'%');
                              })->offset($offset)->limit($this->limit)->get();

        if(count($history) > 0){
            foreach($history as $key => $hist){
                $item = [
                    'ip_address' => $hist->ip,
                    'action' => $hist->action,
                    'location' => $hist->location,
                    'model_used' => $hist->device,
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s', $hist->created_at)->toDateTimeString(),
                    'browser_information' => $hist->ua
                 ];
                 array_push($history_list,$item);
            }
            return static::response(null,static::responseJwtEncoder($history_list), 201, 'success');
        }
        return static::response('No Data Fetched',null, 400, 'failed');
    }


    /**
     * @param $request [image]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSelfie($request){
        $image = $request->image;

        if (preg_match('/data:image\/(gif|jpeg|png);base64,(.*)/i', $image, $matches)) {
            $imageType = $matches[1];
            $imageData = base64_decode($matches[2]);
            $filename = md5($imageData) . '.png';

            $dir = storage_path('app/public') . '/uploads/selfie/';

            if (false === File::exists($dir)) {

                File::makeDirectory($dir, 0755, true);

            }
            $uploadedFile = Storage::disk('selfie')->put($filename, $imageData);

            if ($uploadedFile) {
                return static::response(null,$filename, 201, 'success');
            }
        } else {
            return static::response('Invalid data URL.',null, 422, 'failed');
        }
    }

    /**
     * @param $request [password][passport_id][filepath]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVerified($request){
        $password = $request->password;
        $passport_id = $request->passport_id;
        $filepath = $request->filepath;

        $user = static::get_user();
        if (!Hash::check($password, $user->password)) {
            record_activity(Auth::id(), 'profile', 'Invalid Current Password', 'User', Auth::id(), 'error');
            return static::response('Incorrect Password',null, 422, 'error');
        }

        $data = [
            'user_id' => $user->id,
            'passport_id' => $passport_id,
            'selfie' => $filepath,
        ];
        $verified = static::create_verified_entry($data);

        if($verified['status'] == 200){
                record_activity(Auth::id(), 'profile', 'Update Basic Profile Info', 'Verified', $verified->id, 'success');
            return static::response('Successfully created verified entry!',null, 201, 'success');
        }

        return static::response('Failed to create verified entry!',null, 400, 'error');
    }

     /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerification($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();

        $user = static::get_user($user_id);
        if ($user == null) {
                record_activity(Auth::id(), 'user manager', "User Not found {$user_id}", 'User',$user_id, 'error');
            return static::response('User not found',null, 400, 'error');
        }

        $email = new EmailVerification($user);
        Mail::to($user->email)->send($email);

        $user->verification_resend = date('Y-m-d H:i:s');
        if($user->save()){
                record_activity(Auth::id(), 'user manager', "Resending Emeil Verification Success", 'User', $user_id);
            return static::response('Resending Emeil Verification Success!',null,200, 'success');
        }
        return static::response('Failed to resend verification!',null, 400, 'error');
    }

    /**
     * @param $request [blocker_id][blocked_id][status]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBlockUsers($request){
        $blocker_id = $request->has('blocker_id') ? $request->blocker_id : Auth::id();
        $blocked_id = $request->blocked_id;
        $status = $request->status; // 1 or 0

        $data = ['blocker_id' => $blocker_id, 'blocked_id' => $blocked_id];

        if($status == 1){ // BLOCK USER
            $block = static::block_user($data);
        }else{
            $block = static::unblock_user($data);
        }

        if($block){
            return static::response(null,$block, 201, 'success');
        }

        return static::response(null,null, 400, 'failed');
    }

    /**
     * @param $request [follower_id][followed_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFollowUsers($request){
        $follower_id = $request->has('follower_id') ? $request->follower_id : Auth::id();
        $followed_id = $request->followed_id;
        $status = $request->status; // 1 or 0
        $is_profile = $request->is_profile; // 1 or 0

        $data = ['follower_id' => $follower_id, 'followed_id' => $followed_id];
        if($is_profile == 1){
            
            if($status == 1){
                $follow = static::follow_user($data);
            }else{
                $follow = static::unfollow_user($data);
            }
        }else{
            if($status == 1){
                $block_user = BlockUserTask::where('user_id', $followed_id)->where('task_user_id', $follower_id)->first();
                if($block_user){
                    return static::response('You can\'t follow this user, the user is in your block-list!',null, 400, 'failed');
                }

                $followed = UserFollower::where('user_id', $followed_id)->where( 'follower_id', $follower_id)->first();
                if($followed->status == 1){
                    return static::response('Already followed user!',null, 400, 'failed');
                }else{  
                    $follow = static::follow_user($data);
                }
            }else{
                $follow = static::unfollow_user($data);
            }
            
        }
       
        if($follow){
            if($status == 1){
                event(new NewFollowNotification(User::find($followed_id), User::find($follower_id)));
            }else{
                event(new NewUnfollowNotification(User::find($followed_id), User::find($follower_id)));
            }
            $status_lbl = $status == 1 ? 'followed' : 'unfollowed';
            return static::response('Successfully '. $status_lbl .' user!',$follow, 201, 'success');
        }

        return static::response(null,null, 400, 'failed');
    }

    /**
     * @param $request [user_id][social][status]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSocialLink($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $social = $request->social;
        $status = $request->status; // 1 or 0
        $fbProfilePic = "";
        if($social == "facebook"){
          $fbProfilePic = $request->fbProfilePic;
        }

        $account_name = $request->has('account_name') ? $request->account_name : '';
        $account_id = $request->has('account_id') ? $request->account_id : '';

        $data = ['user_id' => $user_id, 'social' => $social];

        if($status == 1){
            $label = 'linked';
            $checker = SocialConnect::where('user_id',$user_id)->where('social',$social)->first();
            if($checker == null){
                $model = new SocialConnect();
                $model->user_id = $user_id;
                $model->social = $social;
                $model->account_name = $account_name;
                $model->account_id = $account_id;
                $model->status = 1;
                $model->fb_profile_pic = $fbProfilePic;
                $link = $model->save();
            }else{
                $link = static::link_social($data);
            }
        }else{
            $label = 'unlinked';
            $link = static::unlink_social($data);
        }

        if($link){
            return static::response('Successfully '.$label.' '. strtoupper($social) .' account!',null, 201, 'success');
        }

        return static::response(null,null, 400, 'failed');
    }

    public function profileTaskActive($request) {
        // TODO: Implement profileTaskActive() method.

        $user_id =  $request->has('user_id') ? $request->user_id : Auth::id();
        $observer_id =$request->has('user_id') ? $request->user_id : Auth::id();
        $profile_task = Task::with(['user'])->has('user')
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->where('expired_date', '>=', Carbon::now())
            ->where('final_cost', '<>', 0)
            ->orderBy('reward', 'desc')
            ->orderBy(DB::raw('RAND()'))->get();

        $profile_task = array_map(function($profile) use ($observer_id) {
            if($profile['user_id'] != $observer_id) {
                $profile['requirement_status'] = $this->getRequirement($profile['user_id'], $profile['id']);
                $profile['status_str'] = $this->getStatus($profile['id']);
                $profile['available_completer'] = ($profile['total_point'] - $profile['total_rewards']);
            } else {
                $profile['available_completer'] = ($profile['total_point'] - $profile['total_rewards']);
                $profile['status_str'] = $this->getStatus($profile['id']);
            }
            return $profile;
        },$profile_task->toArray());

        return static::response(null,static::responseJwtEncoder($profile_task), 201, 'success');

    }

    /**
     * @param $request [user_id][offset]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userVerificationList($request) {
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $data = [];
        $verified = Verified::where('user_id',$user_id)->offset($offset)->limit($this->limit)->get();
        
        if(count($verified) > 0){
            foreach($verified as $key => $value){
                $item = [
                    'type' => Verified::DEFAULT_TYPE, # TEMP
                    'cryptocurrency_deposit' => 'Unlimited',
                    'cryptocurrency_withdrawal' => 'Unlimited',
                    'verification_threshold' => Verified::THRESHOLD,
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s', $value->created_at)->toDateTimeString(),
                    'status' => $value->status()
                ];

                array_push($data,$item);
            }

            return static::response(null,static::responseJwtEncoder($data), 201, 'success');
        }
        return static::response('No Data Fetched!',null, 400, 'failed');
    }   

    /**
     * @param $request ['gift_recipient_id','gift_coin', 'gift_memo']
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function giftSuperiorCoin($request) {
        $recipient_id = $request->gift_recipient_id;
        $recipient = User::find($recipient_id);
        $gift_coin = $request->gift_coin;
        $gift_memo = $request->gift_memo;
        $giver = Auth::user();

        if($giver->ban > 0){
            return static::response('Your withdrawal request have been denied. You are banned!.',null, 400, 'failed');
        }

        if($recipient->ban > 0){
            return static::response('Your withdrawal request have been denied. Receiver is banned!.',null, 400, 'failed');
        }

        if($giver->status == 0){
            return static::response('Your withdrawal request have been denied. Please activate your account first!.',null, 400, 'failed');
        }

        if($recipient->status == 0){
            return static::response('Your withdrawal request have been denied. Receiver account is inactive!.',null, 400, 'failed');
        }

        if($giver->verified == 0){
            return static::response('Your withdrawal request have been denied. Your account is unverified!.',null, 400, 'failed');
        }

        if($recipient->verified == 0){
            return static::response('Your withdrawal request have been denied. Receiver account is unverified!.',null, 400, 'failed');
        }

        $duration = Carbon::now()->subMinutes(60)->toDateTimeString();
        $check = DbWithdrawal::where('user_id','=',$giver->id)->get();
        foreach ($check as $checklast) {
            if ($checklast->status != 3) {
                if ($checklast->status != 9) {
                    if ($checklast->status != 7) {
                        if ($checklast->status != 14 && $duration < $checklast->updated_at) {
                            if ($checklast->status != 17 && $duration < $checklast->updated_at) {
                                return static::response('Transaction Error ' . $checklast->status,null, 400, 'failed');
                            }
                        }
                    }
                }
            }
        }

        $holdings = (new WalletRepository())->getHoldings($giver->id, true);

        if (gettype((int)$gift_coin) !== 'integer') {
            return static::response('Invalid coin format!',null, 400, 'failed');
        }

        if(!is_numeric($gift_coin)){
            return static::response('Non numeric is not allowed!',null, 400, 'failed');
        }

        if((int) $gift_coin <= 0){
            return static::response('Minimum value must be 1 SUP!',null, 400, 'failed');
        }

        if(gettype($holdings['available']) !== 'integer' && gettype($holdings['available']) !== 'double') { 
            return static::response('Unable to continue..',null, 400, 'failed');
        }
             
        if($holdings['available'] < $gift_coin) {
            return static::response('You don\'t have enough superior coin to give as gift!',null, 400, 'failed');
        }
        
        if($recipient){
            $data = [
                'receiver_id' => $recipient->id,
                'sender_id' => $giver->id,
                'gift_coin' => $gift_coin,
                'gift_memo' => $gift_memo,
            ];

            $saveOk = (new GiftCoinTransaction)->saveGiftTransaction($data);
            if($saveOk) {
                event(new NewGiftCoin($giver, $recipient, Task::TRANSACTION_TYPE[2], $data['gift_coin'], $data['gift_memo']));
                return static::response('You have successfully give the superior coin as a gift!',null, 200, 'success');
            }
        }
        return static::response('Something went wrong while giving gift, please try again!',null, 400, 'failed');
    }

    public function checkIsFollower($request){
        $user_id =  $request->has('user_id') ? $request->user_id : Auth::id();
        if($request->has('username')){
            if($request->username <> ""){
                $user = User::where('username',$request->username)->first();
                $visited_user = $user->id;  
            }
        }

        $follower = UserFollower::where('follower_id', $user_id)
            ->where('user_id',$visited_user)
            ->where('status',1)
            ->get();
       
        if(count($follower) > 0){
                $item = [
                    'status' => count($follower) > 0
                ];
            // return static::response(null,$item, 201, 'success');
            return static::response(null,static::responseJwtEncoder($item), 201, 'success');
        }
        return static::response('No Data Fetched!',null, 400, 'failed');
    }  

    public function unblockUser($request){
        $user_id =  $request->has('user_id') ? $request->user_id : Auth::id();
        $task_user_id = $request->task_user_id;

        $task_delete = BlockUserTask::where('user_id', $user_id)
                    ->where('task_user_id', $task_user_id)
                    ->update(['block' => 0]);

        if($task_delete){
            return static::response(null,'Unblock successful!', 201, 'success');        
        }
        return static::response(null,'Unblock failed!', 401, 'error');        
    }

    public function getFbProfilePictures($request){
        $user_id = $request->has('user_id') ? $request->user_id : Auth::id();
        $social  = $request->social;
        $social_con = SocialConnect::select('fb_profile_pic')
                                    ->where('user_id',$user_id)
                                    ->where('social',$social)
                                    ->where('status',1)->first();
        if($social_con != null){
            return static::response(null,static::responseJwtEncoder($social_con), 201, 'success');
        }

        return static::response('No Data Fetched',null, 400, 'success');
    }

    public function saveOwnReferrer($request){
        $ref_code = $request->ref_code;
        $user = Auth::user();

        if($ref_code == $user->ref_code){
            return static::response('Invalid! Must not be own referral code!',null, 400, 'error');
        }

        $check_code = static::referrer_by_ref_code($ref_code);
        if($check_code == null){
            return static::response('Referral code cannot be found!',null, 400, 'error');
        }else{
            dispatch(new SaveReferral($user, $ref_code));
            return static::response('Successfully saved referrer!',null, 200, 'success');
        }  
    }
}