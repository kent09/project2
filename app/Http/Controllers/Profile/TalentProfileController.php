<?php

namespace App\Http\Controllers\Profile;

use App\User;
use App\Model\TalentProfile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\UtilityTrait;
use App\Classes\Skills;
use App\Classes\CountryList;

class TalentProfileController extends Controller
{
    use UtilityTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(){
        $skills_data = array();
        $data['talent_profile'] = TalentProfile::get();
        
        $skills = new Skills();
        $data['expertise']      = $skills->expertise();
        $data['software']       = $skills->software();
        $data['writing']        = $skills->writing();
        $data['multimedia']     = $skills->multimedia();
        $data['administration'] = $skills->administration();
        $data['engineering']    = $skills->engineering();
        $data['marketing']      = $skills->marketing();
        $data['humanresource']  = $skills->humanresource();
        $data['manufacturing']  = $skills->manufacturing();
        $data['computing']      = $skills->computing();
        $data['translation']    = $skills->translation();
        $data['localservices']  = $skills->localservices();
        $data['otherskills']    = $skills->otherskills();

        if($data){
            return static::response(null,$data, 201, 'success');
        }
        return static::response('No Data Fetched', null, 200, 'success');
    }

    /**
     * @param $request [user_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request){
        $talent_profile = New TalentProfile;
        $talent_profile->user_id = $request->user_id ?? Auth::id();
        $talent_profile->account        = $request->account;
        $talent_profile->title          = $request->title;
        $talent_profile->description    = $request->description;
        $talent_profile->availability   = $request->availability;
        $talent_profile->rate           = $request->rate;
        $talent_profile->expertise      = serialize($request->expertise);
        $talent_profile->skills         = serialize($request->skills);

        if($talent_profile->save()){
            return static::response("Successfully saved user profile!",null, 201, 'success');
        }
        return static::response(null, null, 200, 'success');
    }

    
    /**
     * @param $request [user_id][account]title][description][availability][rate][expertise][skills]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request){
        $user_id = $request->user_id ?? Auth::id();

        $talent_profile = TalentProfile::where('user_id',$user_id)->first();       
        if($talent_profile){
            $talent_profile->account        = $request->account;
            $talent_profile->title          = $request->title;
            $talent_profile->description    = $request->description; // about me description
            $talent_profile->availability   = $request->availability;
            $talent_profile->rate           = $request->rate;
            $talent_profile->expertise      = serialize($request->expertise);
            $talent_profile->skills         = serialize($request->skills);

            if($talent_profile->save()){
                $user = User::where('id',$user_id)->first();
                if($user){
                    $user->location = $request->location;
                    $user->save(); 
                }
                return static::response("Successfully updated user profile!",null, 201, 'success');
            }
        }
        return static::response("No Data Fetched", null, 200, 'success');
    }


     /**
     * @param $request [user_id][status]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request){
        $user_id = $request->user_id ?? Auth::id();
        $status = $request->status; # 1 or 0
        $saveOk = false;
        $model = new TalentProfile();

        $data = array();
        $data['user_id'] = $user_id;
        $data['status']  = $status;
        
        if($data){
            $saveOk = ($model)->toggleProfileStatus($data);
        }

        if($saveOk){
            return static::response(null,$saveOk, 201, 'success');
        }
        return static::response(null, null, 200, 'success');
    }
}
