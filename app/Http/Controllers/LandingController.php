<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Model\AffiliatedSites;
use App\Model\TrustedBusiness;
use App\Traits\UtilityTrait;
use App\Helpers\UtilHelper;
use Carbon\Carbon;
use App\Model\Testimonials;
class LandingController extends Controller
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
     *     path="/api/landing/sites",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Get Affiliated Sites history",
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
     *     @SWG\Response(response=200, description="Successfully Loaded Affiliated Sites history!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSites()
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
                    'affiliated_sites.id as site_id',
                    'affiliated_sites.site_name',
                    'affiliated_sites.site_url',
                    'affiliated_sites.created_at' ];

        $sites_query = AffiliatedSites::join('users as u','u.id','=','affiliated_sites.admin_id')->where('affiliated_sites.status',1);
        if($filter_date <> ''){
            $sites_query = $sites_query->whereDate('affiliated_sites.created_at',$filter_date);
        }  

        if($search_key <> ''){
            $sites_query = $sites_query->where(function ($q) use ($search_key){
                $q->where('affiliated_sites.site_name','LIKE','%'.$search_key.'%')
                  ->orWhere('affiliated_sites.site_url','LIKE','%'.$search_key.'%')
                  ->orWhere('u.name','LIKE','%'.$search_key.'%');
            });
        }

        $sites_count = $sites_query->count();
        $sites = $sites_query->orderByDesc('affiliated_sites.created_at')->limit($limit)->offset($offset)->get($fields)->toArray();
        $list['list'] = $sites;
        $list['count'] = $sites_count;

        if(count($sites) > 0){
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No data fetched!', null, 400, 'failed');

    }

    /**
     * @SWG\POST(
     *     path="/api/landing/sites/add",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Add Affiliated Sites",
     *     @SWG\Parameter(
     *      name="site_name", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="site_url", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully saved Affiliated Sites!"),
     *     @SWG\Response(response=401, description="Error, Failed to save Affiliated Sites!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function addSites()
    {
        $site_name = $this->request->site_name;
        $site_url = $this->request->site_url;
        $admin_id = Auth::id();
        $now = Carbon::now();

        $sites = new AffiliatedSites();
        $sites->site_name = $site_name;
        $sites->site_url = $site_url;
        $sites->admin_id = $admin_id;
        if($sites->save()){
            return static::response('Successfully saved affiliated site!',null, 200, 'success');
        }   
        return static::response('Failed to save affiliated site!',null, 400, 'failed');
    }

    /**
     * @SWG\POST(
     *     path="/api/landing/sites/update/{id}",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Update Affiliated Sites",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully updated Affiliated Sites!"),
     *     @SWG\Response(response=401, description="Error, Failed to update Affiliated Sites!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateSite($id)
    {   
        $site_name = $this->request->site_name;
        $site_url = $this->request->site_url;
        $now = Carbon::now();

        $sites = AffiliatedSites::find($id);
        if($sites){
            $msg = "[site_name]:{$sites->site_name}->{$site_name}, [site_url]:{$sites->site_url}->{$site_url}, [status]:{$sites->status}->1, [updated_at]:{$now}";
            $sites->site_name = $site_name;
            $sites->site_url = $site_url;
            $sites->updated_at = $now;
            if($sites->save()){
                record_admin_activity(Auth::id(), 2, "Update Affiliated Sites [{$sites->id}]=>$msg", 'affiliated_sites',1, 'affiliated_sites');
                return static::response('Successfully updated affiliated site!',null, 200, 'success');
            }   
            return static::response('Failed to update affiliated site!',null, 400, 'failed');
        }
    }

    /**
     * @SWG\POST(
     *     path="/api/landing/sites/delete/{id}",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Delete Affiliated Sites",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully deleted Affiliated Sites!"),
     *     @SWG\Response(response=401, description="Error, Failed to delete Affiliated Sites!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteSite($id)
    {
        $sites = AffiliatedSites::find($id);
        $now = Carbon::now();

        if($sites){
            $msg = "[status]:{$sites->status}->0, [updated_at]:{$now}";
            $sites->status = 0;
            $sites->updated_at = $now;
            if($sites->save()){
                record_admin_activity(Auth::id(), 2, "Delete Affiliated Sites [{$sites->id}]=>$msg", 'affiliated_sites',1, 'affiliated_sites');
                return static::response('Successfully deleted site!',null, 200, 'success');
            }else{
                return static::response('Failed to delete site',null, 400, 'failed');
            }
        }
    }

    public function getBusiness()
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
                    'trusted_business.id as business_id',
                    'trusted_business.business_name',
                    'trusted_business.business_logo',
                    'trusted_business.created_at' ];

        $business_query = TrustedBusiness::join('users as u','u.id','=','trusted_business.admin_id')->where('trusted_business.status',1);
        if($filter_date <> ''){
            $business_query = $business_query->whereDate('trusted_business.created_at',$filter_date);
        }

        if($search_key <> ''){
            $business_query = $business_query->where(function ($q) use ($search_key){
                $q->where('trusted_business.business_name','LIKE','%'.$search_key.'%')
                  ->orWhere('u.name','LIKE','%'.$search_key.'%');
            });
        }

        $business_count = $business_query->count();
        $business = $business_query->orderByDesc('trusted_business.created_at')->limit($limit)->offset($offset)->get($fields)->toArray();
        $business = array_map(function($val){
            $val['business_logo'] = 'public/image/uploads/business_logo/thumbnail/'.$val['business_logo'];
            return $val;
        },$business);

        $list['list'] = $business;
        $list['count'] = $business_count;

        if(count($business) > 0){
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }

        return static::response('No data fetched!', null, 400, 'failed');

    }

    public function addBusiness()
    {
        $business_name = $this->request->business_name;
        $business_logo = $this->request->business_logo;
        $image_format = $this->request->has('image_format') ? $this->request->image_format : 'png';

        $now = Carbon::now();
        $business = TrustedBusiness::where('business_name',$business_name)->where('status',1)->first();
        if($business){
            return static::response('Business name already exists!', null, 400, 'failed');
        }else{
            $business = new TrustedBusiness();
            $business->business_name = $business_name;
            $business->admin_id = Auth::id();
            $business->status = 1;
            if($business->save()){
                $save_logo = static::uploadBusinessLogo($business_logo,$business->id,$image_format);
                if($save_logo){
                    return static::response('Successfully saved trusted business data!',null, 200, 'success');
                }  
            }
            return static::response('Failed to save trusted business data!',null, 400, 'failed');
        }
    }

    public function getTestimonials(){
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

        $testimonials_query = Testimonials::select(['u.name','u.username','testimonials.title',
                                                    'testimonials.content','testimonials.name',
                                                    'testimonials.created_at'])
                                            ->leftJoin('users as u','u.id','=','testimonials.admin_id')
                                            ->where('testimonials.status',1);

        if($filter_date <> ''){
            $testimonials_query = $testimonials_query->whereDate('testimonials.created_at',$filter_date);
        }

        $count = $testimonials_query->count();

        $testimonials = $testimonials_query->orderByDesc('testimonials.created_at')
                                            ->offset($offset)
                                            ->limit($limit)
                                            ->get()->toArray();
        
        $list['count'] = $count;
        $list['list'] = $testimonials;

        if(count($testimonials) > 0){
            return static::response(null,static::responseJwtEncoder($list), 200, 'success');
        }
        return static::response('No data fetched!', null, 400, 'failed');

    }
}
