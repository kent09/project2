<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Model\FaqsHistory;
use App\Traits\UtilityTrait;
use Carbon\Carbon;

class FaqsController extends Controller
{   
    use UtilityTrait;

    protected $request;
    private $limit = 10;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $offset = $this->request->has('offset') ? $this->request->offset : 0;
        $limit = $this->request->has('limit') ? $this->request->limit : $this->limit;
        $search_key = $this->request->has('search_key') ? $this->request->search_key : '';
        $filter_date = "";;
        if($this->request->has('filter_date')){
            if($this->request->filter_date <> ''){
                $filter_date = Carbon::parse($this->request->filter_date)->toDateString();
            }
        }
        $faqs_query = FaqsHistory::select(['faqs_histories.*', 'u.name', 'u.username'])
                                ->leftJoin('users as u','u.id','=','faqs_histories.admin_id')
                                ->where('is_deleted',0)
                                ->groupBy('faqs_histories.category');
        
        if($filter_date <> ''){
            $faqs_query = $faqs_query->whereDate('faqs_histories.updated_at',$filter_date);
        }
        if($search_key <> ''){
            $faqs_query = $faqs_query->where(function($q) use ($search_key){
                $q->where('faqs_histories.category','LIKE', '%'.$search_key.'%')
                  ->orWhere('faqs_histories.title','LIKE', '%'.$search_key.'%')
                  ->orWhere('faqs_histories.content','LIKE', '%'.$search_key.'%')
                  ->orWhere('u.name','LIKE', '%'.$search_key.'%');
            });
        }
        $count = $faqs_query->count();
        $faqs = $faqs_query->orderByDesc('updated_at')->offset($offset)->limit($limit)->get();
        $data = []; $list = [];

        if(count($faqs) > 0){
            foreach($faqs as $row => $value){
                $item = [
                    'date_time' => Carbon::parse($value->updated_at)->toDateTimeString(),
                    'category' => $value->category,
                    'featuredArticles' => self::getFaqByCategory($value->category),
                    // 'title' => $value->title,
                    // 'content' => $value->content,
                    'admin_name' => $value->name,
                    'admin_username' => $value->username
                ];
                $data[] = $item;
            }

            $list['list'] = $data;
            $list['count'] = $count;
            return static::response(null,static::responseJwtEncoder($list), 201, 'success');
        }
        return static::response('No Data Fetched', null, 400, 'error');
    }

    public static function getFaqByCategory($data){
        $underCategory = FaqsHistory::select('faqs_histories.title','faqs_histories.content')
                                    ->where('category','=',$data)->get();

         return $underCategory;
    }


    public function createFaqs()
    {
        $admin_id = $this->request->has('admin_id') ? $this->request->admin_id : Auth::id();
        $category = $this->request->category;
        $title = $this->request->title;
        $content = $this->request->content;

        $faqs = new FaqsHistory();
        $faqs->admin_id = $admin_id;
        $faqs->category = $category;
        $faqs->title = $title;
        $faqs->content = $content;
        if($faqs->save()){
            return static::response('Successfully saved FAQ\'s!',null, 201, 'success');
        }
        return static::response('Failed to save FAQ\'s!',null, 400, 'error');
    }

    public function updateFaqs()
    {
        $admin_id = $this->request->has('admin_id') ? $this->request->admin_id : Auth::id();
        $faqs_id = $this->request->faqs_id;
        $category = $this->request->category;
        $title = $this->request->title;
        $content = $this->request->content;

        $faqs = FaqsHistory::find($faqs_id);
        if($faqs){
            $faqs->category = $category;
            $faqs->title = $title;
            $faqs->content = $content;
            $faqs->admin_id = $admin_id;
            $faqs->updated_at = Carbon::now();
            if($faqs->save()){
                return static::response('Successfully updated FAQ\'s!',null, 201, 'success');
            }
        }
        return static::response('Failed to update FAQ\'s!',null, 400, 'error');
    }

    public function deleteFaqs(){
        $faqs_id = $this->request->faqs_id;

        $faqs = FaqsHistory::find($faqs_id);

        if($faqs){
            $faqs->is_deleted = 1;
            $faqs->deleted_at = Carbon::now();
            $faqs->deleted_by = Auth::id();
            if($faqs->save()){
                return static::response('Successfully deleted FAQ\'s!',null, 201, 'success');
            }
            return static::response('Failed to delete FAQ\'s!',null, 400, 'error');
        }

    }

}
