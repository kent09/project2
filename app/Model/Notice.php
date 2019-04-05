<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    public function status($html=false)
    {
    	switch ($this->attributes['status']) {
    		case 1:
    			if ($html == true){
    				return '<span class="text-success"><b>Active</b></span>';
    			}
    			return 'active';
    			break;
    		case 0:
    			if ($html == true){
    				return '<span class="text-danger"><b>Disabled</b></span>';
    			}
    			return 'disabled';
    			break;
    		
    		default:
    			return '';
    			break;
    	}
    }
}
