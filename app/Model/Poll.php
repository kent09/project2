<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    public function options()
	{
		return $this->hasMany(Option::class, 'poll_id');
    }
    
    public function votes(){
        return $this->hasMany(VoteValue::class,'poll_id','id');
    }

    public function addOption($option)
    {
    	$opt = new Option;
    	$opt->poll_id = $this->attributes['id'];
    	$opt->value = $option;
    	$opt->status = 1;
    	$opt->save();
    }

    public function status($html=false)
    {
        $status = $this->attributes['status'];
        if ($status==1){
            if ($html==true){
                return '<span class="text-success"><b>Active</b></span>';
            }
            return 'Active';
        } else {
            if ($html==true){
                return '<span class="text-danger"><b>Ended</b></span>';
            }
            return 'Ended';
        }
    }

    public function voter_counts()
    {
        return UserVote::where('poll_id', $this->attributes['id'])->count();
    }

    
}
