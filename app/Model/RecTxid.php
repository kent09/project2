<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RecTxid extends Model
{
    //
    protected $table = 'rec_txids';

    public function status()
    {
    	switch ($this->attributes['status']) {
    		case 0:
    			return 'Failed';
    			break;
    		case 1:
    			return 'Received';
    			break;
    	}
	}
}
