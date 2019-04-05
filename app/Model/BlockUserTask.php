<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BlockUserTask extends Model
{
    //
    protected $table = 'block_user_tasks';

    #custom
    public function saveData(array $data) : bool {
        $block = new static;
        $block->user_id = $data['completer_id'];
        $block->task_user_id = $data['task_user_id'];
        if( $block->save() )
            return true;
        return false;
    }
}
