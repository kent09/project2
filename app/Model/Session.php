<?php

namespace App;

use App\Model\UserSessionDetail;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $primaryKey = 'id';

    #scope
    public function scopeIsOnline($query) {
        return $query->where('status', 1);
    }

    #relation
    public function session_detail() {
        return $this->hasOne(UserSessionDetail::class, 'session_id', 'id');
    }

    #custom
    public function saveData(array $data) : bool {
        $session = new static;
        $session->id = $data['id'];
        $session->user_id = $data['user_id'];
        $session->ip_address = $data['ip_address'];
        $session->payload = $data['payload'];
        $session->last_activity = $data['last_activity'];
        if( $session->save() )
            if( (new UserSessionDetail)->saveData([
                'session_id' => $data['id'],
                'user_agent' => $data['user_agent'],
                'login_date' => $data['login_date']
            ])
            )
                return true;
        return false;
    }
}
