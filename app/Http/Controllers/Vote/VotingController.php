<?php

namespace App\Http\Controllers\Vote;

use App\Contracts\VoteInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VotingController extends Controller
{
    protected $vote, $request;

    public function __construct(VoteInterface $vote, Request $request)
    {
        $this->vote = $vote;
        $this->request = $request;
    }
    public function voteRequest(){
        return $this->vote->voteRequest($this->request);
    }
    /**
     * @SWG\POST(
     *     path="/api/vote/voting-poll-list",
     *     tags={"VOTE-API"},
     *     summary="Voting Poll List (Active or Ended)",
     *     @SWG\Parameter(
     *      name="status", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded voting poll data!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getVotingPollList()
    {
    	return $this->vote->getVotingPollList($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/vote/voting-poll-details",
     *     tags={"VOTE-API"},
     *     summary="Voting Poll Details",
     *     @SWG\Parameter(
     *      name="poll_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded voting poll data!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getVotingPollDetails()
    {
    	return $this->vote->getVotingPollDetails($this->request);
    }
}
