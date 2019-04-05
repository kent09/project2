<?php

namespace App\Repository\User;

use App\Events\NewFollowNotification;
use App\User;
use App\Contracts\User\UserInterface;
use App\Traits\UserTrait;
use App\Traits\UtilityTrait;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class UserRepository implements UserInterface
{
    use UserTrait, UtilityTrait;

    protected $query;

    public function followUser($request) {
        // TODO: Implement followUser() method.
        $follower_id = Auth::id();
        $user_id = $request->user_id ?? $request->user_id;

        if($follower_id == $user_id)
            return static::response('Error, Unable to follow yourself!', null, 401, 'error');

        $follow = static::_followUser($user_id, $follower_id);
        if( $follow )
            event(new NewFollowNotification(User::find($user_id), User::find($follower_id)));
            return static::response($follow, $follow, 200, 'success');
        return static::response('Error, Something went wrong!', null, 401, 'error');
    }

    public function memberSearch($request){
        $search_key = $request->search_key;
        $list = [];
        if($search_key == ""){
            return static::response('Please input search key!', null, 401, 'error');
        }
        $this->query = $search_key;
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 0;
        $user_query = User::where('status', 1)
                     ->where('ban', 0)
                     ->where(function($q){
                        $q->orWhere('name', 'LIKE', '%'.$this->query.'%')
                        ->orWhere('username', 'LIKE', '%'.$this->query.'%');
                    });
        $count = $user_query->count();
        $users = $user_query->orderBy('name','asc')->orderBy('username','asc')->offset($offset)->limit($limit)->get();

        $list['count'] = $count;
        $list['data'] = $users;

        if($count > 0){
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched', null, 200, 'success');
    }

    public function getNewlyRegisteredCounter($request){
        $range = $request->has('range') ? $request->range : 'week'; // ['day','week','month','year']
        $subDays = 0;
        switch($range){
            case 'day' :
                $subDays = 1;
                break;
            case 'week' : 
                $subDays =  7;
                break;
            case 'month' : 
                $subDays = 30;
                break;
            case 'year' :
                $subDays = 365;
                break;
            default: 
                $subDays = 7;
                break;
        }

        $date =  Carbon::now()->subDays($subDays)->toDateString();
        $counter = User::whereDate('created_at','>=',$date)->count();
        return static::response(null,$counter, 201, 'success');
    }
}