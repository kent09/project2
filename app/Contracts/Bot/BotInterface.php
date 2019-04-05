<?php

namespace App\Contracts\Bot;

interface BotInterface
{
    public function get_voting_weight($req);
}