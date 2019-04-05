<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserSessionDetail extends Model
{
    //
    protected $table = 'user_session_detail';

    public function saveData(array $data) : bool {
        $session_detail = new static;
        $session_detail->session_id = $data['session_id'];
        $session_detail->user_agent = $data['user_agent'];
        $session_detail->login_date = $data['login_date'];
        $session_detail->logout_date = date('Y-m-d h:m:s');
        if($session_detail->save())
            return true;
        return false;
    }
}
