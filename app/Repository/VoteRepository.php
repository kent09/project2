<?php

namespace App\Repository;

use App\Contracts\VoteInterface;
use App\Model\Poll;
use App\Model\Option;
use App\Model\VoteValue;
use App\Model\UserVote;
use App\Traits\UtilityTrait;
use App\Traits\Manager\UserTrait;
use Carbon\Carbon;
use App\Model\Balance;

class VoteRepository implements VoteInterface
{
    use UtilityTrait, UserTrait;

    protected $query;

    protected $limit = 10;

    public function voteRequest($request){
        $user_id = Auth::id();
        $balance = Balance::where('user_id',$user_id)->first();
     
        $hasVoted = UserVote::where('user_id',$request->user_id)
            ->where('poll_id',$request->poll_id)
            ->get();

        if(count($hasVoted) > 0){
            return static::response('',null, 501, 'User already voted!');
        }

      
        $data = [];
        $voteValue = 0;
        if($balance != null){
            $voteValue = $balance->available;
        }

        $poll = new UserVote();
        $poll->poll_id = $request->poll_id;
        $poll->option_id = $request->option_id;
        $poll->user_id = $request->user_id;

        $value = new VoteValue();
        $value->poll_id = $request->poll_id;
        $value->option_id = $request->option_id;
        $value->votes = $voteValue;

      
        if($poll->save() && $value->save()){
            return static::response('',static::responseJwtEncoder($poll), 200, 'success');
        }else{
            return static::response('',null, 500, 'fail');
        }        
    }

    /**
     * @param $request [status, offset, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVotingPollList($request){
        
        $status = $request->has('status') ? $request->status : 'active'; 
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $search_key = $request->has('search_key') ? $request->search_key : "";
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $data = [];

        if($status == 'ended'){
            $stat = 0;
        }else{
            $stat = 1;
        }

        $polls_query = Poll::with(['options'])->where('status', $stat);
        
        if($filter_date <> ''){
            $polls_query = $polls_query->whereDate('created_at','=',$filter_date);
        }

        if($search_key <> ''){
            $polls_query = $polls_query->where('value','LIKE','%'.$search_key.'%');
        }

        $polls = $polls_query->limit($limit)->get();
       
        if(count($polls) > 0){
            foreach($polls as $key => $value){
                $item = [
                    'poll_id' => $value->id,
                    'items' => $value->value,
                    'voters' => $value->voter_counts(),
                    'start_date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString(),
                    'end_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$value->end_date)->toDateTimeString(),
                    'options' => $value->options
                ];
                array_push($data,$item);
            }
            return static::response('',static::responseJwtEncoder($data), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }

     /**
     * @param $request [poll_id, offset]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVotingPollDetails($request){
        $poll_id = $request->poll_id;
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
        $data = [];

        $poll = Poll::where('id', $poll_id)->first();

        if($poll <> null){
            $data = [
                'title' => $poll->value,
                'start_date' => Carbon::createFromFormat('Y-m-d H:i:s',$poll->created_at)->toDateTimeString(),
                'end_date' =>  Carbon::createFromFormat('Y-m-d H:i:s',$poll->end_date)->toDateTimeString(),
                'status' => $poll->status(false),
                'voter_count' => $poll->voter_counts(),
            ];

            $voting_data = [];
            $options = Option::where('poll_id', $poll_id)->where('status', 1)->get();
            if(count($options) > 0){
                foreach($options as $key => $value){
                    $item = [
                        'option_desc' => $value->value,
                        'option_votes' => $value->points()
                    ];
                    array_push($voting_data,$item);
                }
            }
            $data['voting_data'] = $voting_data;

            $user_vote = UserVote::where('poll_id',$poll_id)->offset($offset)->limit($limit)->get();
            $voter_list = [];
            if(count($user_vote) > 0){
                foreach($user_vote as $key => $value){
                    $user = static::get_user($value->user_id);
                    $item = [
                        'user_id' => $value->user_id,
                        'username' => $user->username,
                        'voting_value' => static::get_user_voting_value($value->user_id)
                    ];

                    if($user->username <> null){
                        array_push($voter_list,$item);
                    }
                }
            }

             # SORTED VOTER LIST BY VOTING VALUE DESC
             $voter_val = array();
             foreach($voter_list as $key => $val){
                 $voter_val[$key] = $val['voting_value'];
             }
             array_multisort($voter_val,SORT_DESC,$voter_list);
             # END SORTING

            $data['voter_list'] = $voter_list;

            return static::response('',static::responseJwtEncoder($data), 200, 'success');
        }
        return static::response('No Data Fetched', null, 201, 'success');
    }
}