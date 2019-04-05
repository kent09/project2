<?php

namespace App\Contracts;

interface VoteInterface
{
    public function getVotingPollList($request);

    public function getVotingPollDetails($request);

    public function voteRequest($request);
}