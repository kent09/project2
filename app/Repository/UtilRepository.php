<?php

namespace App\Repository;

use App\Model\Settings;
use App\Model\AdminActivity;
use App\Model\ReferralReward;
use App\Model\BlockedUser;
use App\Model\UserFollower;
use App\Model\VisitorCounter;
use App\Model\GroupChat;
use Illuminate\Http\Request;
use App\Traits\Manager\UserTrait;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Spatie\Emoji\Emoji;
use function GuzzleHttp\json_encode;

class UtilRepository
{
    public $key;
    public $value;
    public $description;

    use UserTrait;

    public function settings($key)
    {
        $settings = Settings::where('key', $key)->first();
        if ($settings==null){
            $this->key = null;
            $this->value = null;
            $this->description = null;
            return $this;
        }
        $this->key = $settings->key;
        $this->value = $settings->value;
        $this->description = $settings->description;
        return $this;
    }

    public static function paginate($collection, int $parts, int $page)
    {
        $offset = ($page - 1) * $parts;
        $limit = $page * $parts;

        $collection = $collection->slice($offset)->take($limit);

        $collection = $collection->flatten()->all();

        return $collection;
    }

    public function record_activity($user_id, $field, $action, $table=null, $row_id=0, $request_status='success')
    {
        return true;
    }

    public function record_admin_activity($admin_id, $type, $action, $field, $status, $category = null, $affected_user_id = 0) 
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : null;


        $activity = new AdminActivity;
        $activity->admin_id = $admin_id;
        $activity->category = $category;
        $activity->affected_user_id = $affected_user_id;
        $activity->type = $type;
        $activity->action = $action;
        $activity->field = $field;
        $activity->status = $status;
        $activity->ua = $ua;
        $activity->ip = $ip;

        if($activity->save()){
            return true;
        }
        return false;
    }

    public function set_referral_reward($user_id = 0, $referral_id, $reward=0, $type)
    {
        if ($user_id == 0){
            $user_id = Auth::id();
        }
        if ($type == 1){
            $detail = "Successful Signup";
        } elseif ($type == 2){
            $detail = "Successful Social Media Account Connection";
        } else {
            $detail = "Other";
        }
        $referral_reward = ReferralReward::where('user_id', $user_id)->where('referral_id', $referral_id)->where('type', $type)->first();
        if ($referral_reward == null){
            $referral_reward = new ReferralReward;
            $referral_reward->user_id = $user_id;
            $referral_reward->referral_id = $referral_id;
            $referral_reward->type = $type;
            $referral_reward->reward = $reward;
            $referral_reward->detail = $detail;
            $referral_reward->version = 2;
            $referral_reward->save();

            record_activity($user_id, 'referral', "Earned {$reward} for {$detail} (from cron job)", 'ReferralReward', $referral_reward->id);
        } else {
            $referral_reward->reward = $reward;
            $referral_reward->detail = $detail;
            $referral_reward->save();
        }
    }

    public function online_users_list($user_id){
        $redis = Redis::connection();
 
        //added blocked user
        $blocked = BlockedUser::where('blocker_id', $user_id) ->where('status',1)->get(['blocked_id']);
        $followed_users = UserFollower::where('follower_id', $user_id)->whereNotIn('user_id',$blocked)->get(['user_id']);
        $user_lists = [];
        $online_users = [];
        $offline_users = [];
        $user_group = [];

        if (count($followed_users)>0){
            foreach ($followed_users as $user) {
                $state = VisitorCounter::where('user_id', $user->user_id)->orderBy('updated_at', 'desc')->first();
                if ($state!=null){
                    if($state->status == 1){
                        $status = 'online';
                    }else{
                        $status = 'offline';
                    }
                    if(json_decode($redis->get('p_status_'.$state->user_id)) == null){
                        $redis->set('p_status_'.$state->user_id, json_encode($status));
                    }
                   
                    $item['username'] = $state->user->username;
                    $item['user_id'] = $state->user_id;
                    $item['name'] = $state->user->name;
                    $item['avatar'] = static::get_avatar($state->user_id);
                    $item['mood'] = json_decode($redis->get('p_mood_'.$state->user_id));
                    $item['status'] = json_decode($redis->get('p_status_'.$state->user_id));
                    $item['unread'] = json_decode($redis->get('p_unread_'.$state->user_id)) ?? 0;
                    // $item['chat_status'] = static::is_blocked_user($user_id,$state->user_id) ? 1 : 0;
                    $key = 'chat:notif:counter_'.Auth::id().'_'.$state->user_id;
                    if (Auth::id() > $state->user_id) {
                        $key = 'chat:notif:counter_' . $state->user_id . '_' . Auth::id();
                    }
                    $item['missed_counts'] = json_decode($redis->get($key));
                    if ($state->status == 1){
                        array_push($online_users, $item);
                    } else {
                        array_push($offline_users, $item);
                    }
                }
            }
        }

        $groups = GroupChat::where('group_members', 'LIKE', '%"'.$user_id.'"%')->get(['id', 'group_name', 'group_members', 'status']);
        if (count($groups) > 0){
            foreach ($groups as $group) {
                $item['group_id'] = $group->id;
                $item['group_name'] = $group->group_name;
                $item['group_members'] = str_replace('"', '', $group->group_members);
                $item['avatar'] = '/image/group-default.png';
                $item['status'] = $group->status;
                array_push($user_group, $item);
            }
        }
        $user_lists['nice'] = crc32($user_id);
        $user_lists['online'] = $online_users;
        $user_lists['offline'] = $offline_users;
        $user_lists['user_group'] = $user_group;

        return $user_lists;
    }

    public function emoji($type=null)
    {
        switch ($type) {
            case 'grinning':
                return Emoji::grinningFace();
                break;
            case 'laughing':
                return Emoji::faceWithTearsOfJoy();
                break;
            case 'sweat':
                return Emoji::faceWithOpenMouthAndColdSweat();
                break;
            case 'winking':
                return Emoji::winkingFace();
                break;
            case 'savoring':
                return Emoji::faceWithStuckOutTongue();
                break;
            case 'heart-eyes':
                return Emoji::smilingCatFaceWithHeartShapedEyes();
                break;
            case 'thinking':
                return Emoji::thinkingFace();
                break;
            case 'neutral':
                return Emoji::neutralFace();
                break;
            case 'persevering':
                return Emoji::perseveringFace();
                break;
            case 'sad':
                return Emoji::cryingFace();
                break;

            default:
                return Emoji::faceWithoutMouth();
                break;
        }

    }
}