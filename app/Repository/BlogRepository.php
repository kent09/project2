<?php

namespace App\Repository;

use App\Contracts\BlogInterface;
use App\Traits\UtilityTrait;
use App\Traits\Manager\UserTrait;
use App\Model\Blog;
use App\Model\BlockedUser;
use App\Model\UserFollower;
use App\Helpers\UtilHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BlogRepository implements BlogInterface
{   
    use UtilityTrait, UserTrait;
    protected $limit = 10;

    public function getTopBloggers($request)
	{
        $user_id = Auth::id();
        $offset = $request->has('offset') ? $request->offset : 0;
        $limit = $request->has('limit') ? $request->limit : $this->limit;
       
        $bloggers = Blog::with('user')->whereHas('user', function ($user) {
                        $user->with('withRole')->whereHas('withRole', function ($role) {
                            $role->with('limitations')->whereHas('limitations', function ($limitations) {
                                $limitations->where('slug', 'kblog-featured-blogger')->where('value', 1);
                            });
                        })->where('ban', 0)->where('status', 1)->where('verified', 1)->where('agreed', 1);
                    })->leftJoin('blog_user as bu','bu.blog_id','=','blog_post.blog_id')
                    ->join('laravellikecomment_likes as llc' ,'llc.item_id', '=' ,'blog_post.blog_id')
                    ->groupBy('blog_post.user_id')->orderBy(DB::raw('count(vote)'),'desc')
                    ->selectRaw('blog_post.user_id')
                    ->offset($offset)->limit($limit)->get()->toArray();


        $bloggers = array_map(function($val){
            $val['is_follower'] = null;
            $val['is_following'] = null;
            $val['is_self'] = true;
            $val['total_blog_post'] = static::count_blog_post($val['user_id']);
            $val['total_blog_points'] = static::sum_points($val['user_id']);
            return $val;
        },$bloggers);

        if(count($bloggers) > 0){
            return static::response(null,static::responseJwtEncoder($bloggers), 200, 'success');
        }
        return static::response('No data fetched!', null, 400, 'failed');
    }

}
