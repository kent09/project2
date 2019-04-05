<?php

namespace App\Http\Controllers\LeaderBoard;


use App\Contracts\LeaderBoardInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeaderBoardController extends Controller
{
    protected $leader;
    protected $request;

    public function __construct(LeaderBoardInterface $leader, Request $request)
    {
        $this->leader = $leader;
        $this->request = $request;
    }

     /**
     * @SWG\POST(
     *     path="/api/leaderboard/referral",
     *     tags={"LEADERBOARD-API"},
     *     summary="My Referrals",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="level", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load My Referrals"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function referral()
    {   
        return $this->leader->referral($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/leaderboard/general",
     *     tags={"LEADERBOARD-API"},
     *     summary="General Leaderboard",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="range", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load General leaderboard"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function general(){
        return $this->leader->general($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/leaderboard/own",
     *     tags={"LEADERBOARD-API"},
     *     summary="My Leaderboard",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="range", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="level", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Load My leaderboard"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function own(){
        return $this->leader->own($this->request);
    }
}
