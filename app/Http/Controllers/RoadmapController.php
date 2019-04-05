<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\UtilityTrait;
use App\Helpers\UtilHelper;
use App\Model\Roadmap;
use Carbon\Carbon;

class RoadmapController extends Controller
{
    use UtilityTrait;

    protected $request;
    protected $limit = 10;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

     /**
     * @SWG\POST(
     *     path="/api/landing/roadmap",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Get Roadmap history",
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="search_key", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Loaded roadmap history!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function index()
    {   
        $list = [];
        $limit = $this->request->has('limit') ? $this->request->limit : $this->limit;
        $offset = $this->request->has('offset') ? $this->request->offset : 0;
        $search_key = $this->request->has('search_key') ? $this->request->search_key : "";
        $filter_date = "";
        if($this->request->has('filter_date')){
            if($this->request->filter_date <> ''){
                $filter_date = Carbon::parse($this->request->filter_date)->toDateString();
            }
        }
        $fields = [ 'u.name as admin_name',
                    'u.username as admin_username',
                    'u.id as user_id',
                    'roadmaps.id as roadmap_id',
                    'roadmaps.month',
                    'roadmaps.year',
                    'roadmaps.title',
                    'roadmaps.body',
                    'roadmaps.created_at' ];

        $roadmap_query = Roadmap::join('users as u','u.id','=','roadmaps.admin_id')->where('roadmaps.status',1);

        if($filter_date <> ''){
            $roadmap_query = $roadmap_query->whereDate('roadmaps.created_at',$filter_date);
        }

        if($search_key <> ''){
            $roadmap_query = $roadmap_query->where(function ($q) use ($search_key){
                $q->where('roadmaps.title','LIKE','%'.$search_key.'%')
                  ->orWhere('roadmaps.body','LIKE','%'.$search_key.'%')
                  ->orWhere('u.name','LIKE','%'.$search_key.'%')
                  ->orWhere('roadmaps.year','=',$search_key);
            });
        }

        $roadmap_count = $roadmap_query->count();
        $roadmap = $roadmap_query->orderByDesc('roadmaps.created_at')->limit($limit)->offset($offset)->get($fields)->toArray();
        $roadmap = array_map(function($val){
                        $val['month'] = (new Roadmap())->getMonth($val['month']);
                        return $val;
                },$roadmap);
        $list['list'] = $roadmap;
        $list['count'] = $roadmap_count;

        if(count($roadmap) > 0){
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No data fetched!', null, 400, 'failed');

    }

     /**
     * @SWG\POST(
     *     path="/api/landing/roadmap/add",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Add Roadmap",
     *     @SWG\Parameter(
     *      name="year", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="month", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="title", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="body", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully saved roadmap!"),
     *     @SWG\Response(response=401, description="Error, Failed to save roadmap!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function createRoadmap()
    {
        $year = $this->request->year;
        $month = $this->request->month;
        $title = $this->request->title;
        $body = $this->request->body;
        $now = Carbon::now();
        $roadmap = Roadmap::where('year',$year)->where('month',$month)->where('status',1)->first();
        $msg = "[title]:{$roadmap->title}->{$title}, [body]:{$roadmap->body}->{$body}, [status]:{$roadmap->status}->1, [updated_at]:{$now}";
        if($roadmap){
            $roadmap->title = $title;
            $roadmap->body = $body;
            $roadmap->updated_at = $now;
            if($roadmap->save()){
                record_admin_activity(Auth::id(), 2, "Roadmap [{$roadmap->id}]=>$msg", 'roadmaps',1, 'roadmaps');
                return static::response('Successfully saved roadmap',null, 200, 'success');
            }
        }else{
            $roadmap = new Roadmap;
            $roadmap->month = $month;
            $roadmap->year = $year;
            $roadmap->title = $title;
            $roadmap->body = $body;
            $roadmap->admin_id = Auth::id();
            $roadmap->status = 1;
            if($roadmap->save()){
                return static::response('Successfully saved roadmap',null, 200, 'success');
            }
        }
        return static::response('Failed to save roadmap',null, 400, 'failed');
    }

     /**
     * @SWG\POST(
     *     path="/api/landing/roadmap/update/{id}",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Update Roadmap",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated roadmap!"),
     *     @SWG\Response(response=401, description="Error, Failed to update roadmap!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateRoadmap($id)
    {   
        $title = $this->request->title;
        $body = $this->request->body;
        $roadmap = Roadmap::find($id);
        $now = Carbon::now();
        if($roadmap){
            $msg = "[title]:{$roadmap->title}->{$title}, [body]:{$roadmap->body}->{$body}, [status]:{$roadmap->status}->1, [updated_at]:{$now}";
            $roadmap->title = $title;
            $roadmap->body = $body;
            $roadmap->updated_at = $now;
            if($roadmap->save()){
                record_admin_activity(Auth::id(), 2, "Update Roadmap [{$roadmap->id}]=>$msg", 'roadmaps',1, 'roadmaps');
                return static::response('Successfully saved roadmap',null, 200, 'success');
            }else{
                return static::response('Failed to save roadmap',null, 400, 'failed');
            }
        }
        return static::response('Failed to locate roadmap data!',null, 400, 'failed');
    }

     /**
     * @SWG\POST(
     *     path="/api/landing/roadmap/delete/{id}",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Delete Roadmap",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully deleted roadmap!"),
     *     @SWG\Response(response=401, description="Error, Failed to delete roadmap!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteRoadmap($id)
    {
        $roadmap = Roadmap::find($id);
        $now = Carbon::now();

        if($roadmap){
            $msg = "[status]:{$roadmap->status}->0, [updated_at]:{$now}";
            $roadmap->status = 0;
            $roadmap->updated_at = $now;
            if($roadmap->save()){
                record_admin_activity(Auth::id(), 2, "Delete Roadmap [{$roadmap->id}]=>$msg", 'roadmaps',1, 'roadmaps');
                return static::response('Successfully deleted roadmap',null, 200, 'success');
            }else{
                return static::response('Failed to delete roadmap',null, 400, 'failed');
            }
        }
        return static::response('Failed to delete roadmap data!',null, 400, 'failed');
    }
}
