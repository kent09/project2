<?php

namespace App\Repository\Bot;

use App\User;
use App\Contracts\Bot\BotInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BotRepository implements BotInterface
{
    public function get_voting_weight($req)
    {
        $user = User::find(Auth::id());
        if ($user === null) {
            return res('Forbidden for you', $null, 403);
        }
        $passes = is_limitation_passed('bot-voting-weight-calculator', $user->id);
        if (!$passes['passed']) {
            return res('Forbidden for you', $null, 403);
        }
        if ($passes['role'] != 'admin' && $passes['data']->value == 0) {
            return res('Forbidden for you', $null, 403);
        }
        $validator = Validator::make($req->all(), [
            'following' => 'required|min:0|max:3',
            'task_for_today' => 'required',
            'total_rewards' => 'required',
            'type' => 'required|string',
        ]);
        if ($validator->fails()) {
            return res('Validation Failed', $validator->errors(), 412);
        }

        $limitation_info = limitation_info('bot-voting-weight', $user->id);
        $multiplier = 0;
        if ($limitation_info['value'] !== null) {
            $multiplier = $limitation_info['value'];
        }
        $payload = http_build_query([
            'following' => $req->following,
            'task_for_today' => $req->task_for_today,
            'total_rewards' => $req->total_rewards,
            'type' => $req->type,
            'multiplier_percentage' => $multiplier
        ]);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $payload,
            ]
        ];
        $content = stream_context_create($opts);
        $result = file_get_contents(config('app.bot_host') . '/api/get-weight', false, $content);

        $result = json_decode($result);
        return res('Success', $result);
    }
}