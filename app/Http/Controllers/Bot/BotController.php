<?php

namespace App\Http\Controllers\Bot;

use Illuminate\Http\Request;
use App\Contracts\Bot\BotInterface;
use App\Http\Controllers\Controller;

class BotController extends Controller
{
    protected $bot;

    public function __construct(BotInterface $bot)
    {
        $this->bot = $bot;
    }

    public function get_voting_weight()
    {
        return $this->bot->get_voting_weight(request());
    }
}
