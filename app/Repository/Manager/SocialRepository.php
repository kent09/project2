<?php

namespace App\Repository\Manager;

use Illuminate\Http\Request;

use App\Helpers\UtilHelper;
use App\Model\SocialMedia;
use App\Model\SocialConnect;
use App\Model\SocialConnectHistory;
use App\Traits\UtilityTrait;
use App\Traits\Manager\UserTrait;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Manager\SocialInterface;
use Carbon\Carbon;

class SocialRepository implements SocialInterface
{
   
    use UserTrait, UtilityTrait;

     /**
     * @param $request [sc_id]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hardUnlink($request)
    {   
        $sc_id = $request->sc_id;
        $now = date('Y-m-d H:i:s');
        $social = SocialConnect::find($sc_id);

        if ($social == null){
            record_activity(Auth::id(), 'social manager', "Hard-Unlink Account [{$sc_id}]=>Invalid Social Information", 'SocialConnect', $sc_id, 'error');
            record_admin_activity(Auth::id(), 2, "Hard-Unlink Account [{$sc_id}]=>Invalid Social Information", 'social', 0, 'hard-unlink');
            return static::response('Invalid Social Information',null, 400, 'failed');
        } else {
            $msg = "[status]:{$social->status}->3, [hard_unlink_request_reason]->{$social->reason}, [hard_unlink_request_at]->{$social->hard_unlink_request_at}, [hard_unlinked_at]->{$now}, [sc_id]->{$social->id}";
            $social->status = 3;
            $social->hard_unlink_status = SocialConnect::hu_status_approved;
            
            if($social->save()){
                $user = static::get_user($social->user_id);
                $txt = 'The ' . title_case($social->social) . ' social media account of ' . $user->name . ' has successfully hard-unlinked from the kryptonia.io account.';
                record_activity(Auth::id(), 'social manager', "Hard-Unlink Account [{$sc_id}]=>" . $msg, 'SocialConnect', $sc_id, 'success');
                record_admin_activity(Auth::id(), 2, "Hard-Unlink Account [{$sc_id}]=>" . $msg, 'social', 1, 'hard-unlink', $social->user_id);
                
                $social_media = SocialMedia::where('social',$social->social)->first();
                $data = [
                    'user_id' => $social->user_id,
                    'social_id' => $social_media->id,
                    'account_name' => $social->account_name,
                    'account_id' => $social->account_id,
                    'status' => $social->status,
                    'hard_unlink_status' => $social->hard_unlink_status,
                    'hard_unlink_reason' => $social->reason
                ];
                $saveHistory = (new SocialConnectHistory())->saveData($data);
                return static::response($txt,null, 200, 'success');
            }else{
                return static::response('Failed to Hard-Unlink Account!',null, 400, 'failed');
            }
        }
    }

    /**
     * @param $request ['sc_id,'reason']
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hardUnlinkRequest($request)
    {   
       $sc_id = $request->sc_id;
       $reason = $request->reason;
       $requested_status = SocialConnect::hu_status_requested;
    
       if($reason == ''){
            return static::response('Hard-unlink reason is required!',null, 400, 'failed');
       }else{
           $social_con = SocialConnect::find($sc_id);
           if($social_con <> null){
                $social_con->reason = $reason;
                $social_con->hard_unlink_status = $requested_status;
                $social_con->hard_unlink_request_at = Carbon::now();
                if($social_con->save()){
                    $social = SocialMedia::where('social',$social_con->social)->first();
                    $data = [
                        'user_id' => $social_con->user_id,
                        'social_id' => $social->id,
                        'account_name' => $social_con->account_name,
                        'account_id' => $social_con->account_id,
                        'status' => $social_con->status,
                        'hard_unlink_status' => $social_con->hard_unlink_status,
                        'hard_unlink_reason' => $social_con->reason
                    ];
                    $saveHistory = (new SocialConnectHistory())->saveData($data);
                    return static::response('Successfully requested hard-unlink!',$requested_status, 200, 'success');
                }
           }
       }
       return static::response('Failed to request hard-link!',null, 400, 'failed');
    }

    /**
     * @param $request [sc_id, reason]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deniedHardUnlinkRequest($request)
    {   
        $sc_id = $request->sc_id;
        $reason = $request->reason;
        $now = date('Y-m-d H:i:s');

        if($reason == ""){
            return static::response('Deny hard-unlink reason is required!',null, 400, 'failed');
        }

        $social = SocialConnect::find($sc_id);

        if ($social == null){
            record_activity(Auth::id(), 'social manager', "Deny Hard-Unlink Account [{$sc_id}]=>Invalid Social Information", 'SocialConnect', $sc_id, 'error');
            record_admin_activity(Auth::id(), 2, "Deny Hard-Unlink Account [{$sc_id}]=>Invalid Social Information", 'social', 0, 'denied-hard-unlink');
            return static::response('Invalid Social Information',null, 400, 'failed');
        } else {
            $msg = "[hard_unlink_status]:{$social->hard_unlink_status}->2, [reason]:{$reason}, [denied_hard_unlink_at]:{$now}";
            $social->hard_unlink_status = SocialConnect::hu_status_declined;

            if($social->save()){
                $user = static::get_user($social->user_id);
                $txt = 'The ' . title_case($social->social) . ' social media account of ' . $user->name . ' has successfully denied hard-unlinked from the kryptonia.io account.';
                record_activity(Auth::id(), 'social manager', "Hard-Unlink Account [{$sc_id}]=>" . $msg, 'SocialConnect', $sc_id, 'success');
                record_admin_activity(Auth::id(), 2, "Denied Hard-Unlink Account [{$sc_id}]=>" . $msg, 'social', 1, 'denied-hard-unlink');
                
                $social_media = SocialMedia::where('social',$social->social)->first();
                    $data = [
                        'user_id' => $social->user_id,
                        'social_id' => $social_media->id,
                        'account_name' => $social->account_name,
                        'account_id' => $social->account_id,
                        'status' => $social->status,
                        'hard_unlink_status' => $social->hard_unlink_status,
                        'disapproved_reason' => $reason
                    ];
                $saveHistory = (new SocialConnectHistory())->saveData($data);
                return static::response($txt,null, 200, 'success');
            }else{
                return static::response('Failed to Hard-Unlink Account!',null, 400, 'failed');
            }
        }
        return static::response('Invalid Social Information',null, 400, 'failed');
    }
}