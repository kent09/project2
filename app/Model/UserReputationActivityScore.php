<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserReputationActivityScore extends Model
{
    //
    protected $table = 'user_reputation_activity_scores';

    public function saveActivityScoreReputation($type = null, $user_id = null) {

        if(!is_null($type) && !is_null($user_id)) {

            $data = static::where('user_id', $user_id)->first();

            if($data) {

                if($type === 'completed') {
                    $data->activity_score += 1;
                    if($data->save())
                        return $data;
                    return false;
                }

                if($type === 'reinstate') {
                    $data->activity_score += 1;
                    if($data->save())
                        return $data;
                    return false;
                }

                if($type === 'revoked') {
                    $data->activity_score -= 1;
                    if($data->save())
                        return $data;
                    return false;
                }
                if($type === 'blocked') {
                    $data->activity_score -= 1;
                    if($data->save())
                        return $data;
                    return false;
                }

            } else {
                if($type === 'completed') {
                    $this->attributes['user_id'] = $user_id;
                    $this->attributes['activity_score'] = 1;
                    if($this->save())
                        return $this;
                    return false;
                }
            }

        }
        return false;
    }
}
