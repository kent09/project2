<?php

namespace App\Repository;

use App\Contracts\ChatInterface;
use App\User;
use App\Model\VisitorCounter;
use App\Model\GroupChat;
use App\Model\BlockedUser;
use App\Model\UserFollower;
use Illuminate\Support\Facades\Auth;
use App\Helpers\UtilHelper;
use App\Traits\UtilityTrait;
use Illuminate\Support\Facades\Redis;
use App\Events\NewPrivateChat;
use App\Events\NewGroupChat;
use Illuminate\Support\Facades\Validator;
use App\Traits\Manager\UserTrait;
use Carbon\Carbon;
use App\Model\RawQueries;
class ChatRepository implements ChatInterface
{
    use UtilityTrait, UserTrait;

    public function getChatUsersList()
	{
		// GET ALL CURRENTLY ONLINE USERS
        $current_online_users = VisitorCounter::where('status', 1)->get(['user_id']);
        $list = [];
		if (count($current_online_users)>0){
			foreach ($current_online_users as $user) {
				$redis = Redis::connection('chat');
                $user_lists = online_users_list($user->user_id);
                $list[] = $user_lists;
				$user_lists = json_encode($user_lists);
                $redis->publish('chat-list-channel', $user_lists);
                
            }
            $redis->publish('chat-active', Auth::id()); 
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No current online users!', null, 400, 'failed');
    }
    
    public function getUserChatList($request){
        $user_id = Auth::id();
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;


        $chats = (new RawQueries())->userChatList($request);
        
        if(count($chats) > 0){
            return static::response(null,static::responseJwtEncoder($chats), 200, 'success');
        }
        return static::response('No data fetched!', null, 400, 'failed');
    }
    
    public function getAllPrivate($user_id){
        $chat_id = Auth::id().'_'.$user_id;
        $data = [];
		if (Auth::id() > $user_id){
			$chat_id = $user_id.'_'.Auth::id();
        }
  
		$redis = Redis::connection('chat');
        $chats = $redis->lrange('chat:private.'.$chat_id, 0, -1);
        
        if(count($chats) > 0){
            foreach($chats as $key => $value){
                $chat = json_decode($value);
                $item = [
                    'who' => $chat->receiver,
                    'who_username' => static::get_user($chat->receiver)->username,
                    'who_avatar' => env('PROFILE_IMAGE').$chat->receiver.'/avatar.png',
                    'message' => $chat->msg,
                    'time' => Carbon::parse($chat->datetime)->toDateTimeString(),
                    'from' => $chat->sender,
                    'from_username' => static::get_user($chat->sender)->username,
                    'from_avatar' => env('PROFILE_IMAGE').$chat->sender.'/avatar.png',
                ];

                $data[] = $item;
            }

            return static::response(null,static::responseJwtEncoder($data), 200, 'success');
        } 
        return static::response('No data fetched!', null, 400, 'failed');
    }

    public function getAllGroup($group_id)
	{
		$redis = Redis::connection('chat');
        $chats = $redis->lrange('chat:group.'.$group_id, 0, -1);
        $data = [];

        if(count($chats) > 0){
            foreach($chats as $key => $value){
                $chat = json_decode($value);
                $group = GroupChat::find($chat->receiver);
                $item = [
                    'who' => $chat->receiver,
                    'group_name' => $group->group_name,
                    'message' => $chat->msg,
                    'time' => Carbon::parse($chat->datetime)->toDateTimeString(),
                    'from' => $chat->sender,
                    'from_username' => static::get_user($chat->sender)->username,
                    'from_avatar' => env('PROFILE_IMAGE').$chat->sender.'/avatar.png',
                ];

                $data[] = $item;
            }
            return static::response(null,static::responseJwtEncoder($data), 200, 'success');
        }

		return static::response('No data fetched!', null, 400, 'failed');
    }
    
    public function getEmojis($type){
        $emoji = emoji($type);
        return static::response(null,static::responseJwtEncoder($emoji,false), 200, 'success');
    }

    public function sendToPrivate($request){
        event(new NewPrivateChat($request->msg, $request->receiver, $request->sender));
		return "true";
    }
    
    public function sendToGroup($request){
        $group = GroupChat::find($request->group_id);
		$members = json_decode($group->group_members);
		$index = array_search((string)Auth::id(), $members);
		if ($index < 0){
			$data = [
				'message' => 'Not Allowed',
				'receiver' => $request->group_id
			];	
			return static::response(null,static::responseJwtEncoder($data,false), 200, 'success');
		}
		event(new NewGroupChat($request->msg, $request->group_id, $request->sender_id));
		return 'true';
    }

    public function createGroupChat($request){
        Validator::make($request->all(),[
			'group_name' => 'required|min:3|max:20|unique:group_chats,group_name'
        ]);
        $members = $request->members;
		array_push($members, (string)Auth::id());
		$group = new GroupChat;
		$group->group_name = $request->group_name;
		$group->group_members = json_encode($members);
        
        if($group->save()){
            return static::response('Successfully created group chat!',null, 200, 'success');
        }

        return static::response('Failed to create group chat!', null, 400, 'failed');
    }

    public function updateGroupChat($request){
        Validator::make($request->all(),[
			'group_name' => 'required|min:3|max:20|unique:group_chats,group_name,'.$request->group_id
		]);
		$members = $request->members;
		array_push($members, (string)Auth::id());
		$group = GroupChat::find($request->group_id);
		$group->group_name = $request->group_name;
		$group->group_members = json_encode($members);
		$group->save();

		foreach ($members as $member){
			$data = [
				'group_id' => $request->group_id,
				'group_name' => $group->group_name,
				'avatar' => '/image/group-default.png',
				'status' => $group->status,
				'members' => $members,
				'nice' => crc32((int)$member),
			];
			$data = json_encode($data);
			$redis = Redis::connection('chat');
			$redis->publish('updated-group-channel', $data);
		}

		return static::response('Successfully updated group chat!',null, 200, 'success');
    }

    public function leaveGroupChat($request){
        $group = GroupChat::find($request->group_id);
		if ($group){
			$members = json_decode($group->group_members);
			if (count($members)>0){
				foreach ($members as $key => $val){
					if ($val == Auth::id()){
						unset($members[$key]);
					}
				}
			}
			$group->group_members = json_encode($members);
            $group->save();
            
            return static::response('Successfully leaved group chat!',compact('members'), 200, 'success');
        }
        
        return static::response('Failed to leave group chat!', null, 400, 'failed');
    }

    public function saveToRedis($request){
        $key = $request->key;
        $value = $request->val;

        $redis = Redis::connection('chat');
		$val = json_encode($value);
        $redis->set($key, $val);
        return static::response('Successfully saved to redis!',null, 200, 'success');
    }

    public function deleteFromRedis($request)
	{
		$redis = Redis::connection('chat');
        $redis->del($request->key);
        return static::response('Successfully deleted key to redis!',null, 200, 'success');
	}
}
