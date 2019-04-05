<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockedDomain extends Model
{
    public function status($html=false)
    {
    	if ($this->attributes['status']==1){
    		if ($html==true){
    			return '<span class="text-success"><b><span class="fa fa-check"></span></b></span>';
    		}
    		return 'active';
    	} else {
    		if ($html==true){
    			return '<span class="text-danger"><b><span class="fa fa-times"></span></b></span>';
    		}
    		return 'disabled';
    	}
    }

    public function remainingDays()
    {
        $duration = settings('domain_block_duration')->value;
        $week = Carbon::now()->subDays(7)->toDateTimeString();
        $domains = BlockedDomain::where('updated_at', '<', $week)->where('status', 1)->get(['id']);
        if (count($domains)>0){
            foreach ($domains as $domain) {
                $item = BlockedDomain::find($domain->id);
                $item->status = 0;
                $item->save();
            }
        }
        $domain = BlockedDomain::where('domain', $this->attributes['domain'])->where('updated_at', '>=', $week)->where('status', 1)->first();
        if ($domain!=null){
            $updated = Carbon::createFromFormat('Y-m-d H:i:s', $domain->updated_at);
            $days = Carbon::now()->diffInDays($updated);
            return $duration-$days;
        }
        return 0;
    }
}
