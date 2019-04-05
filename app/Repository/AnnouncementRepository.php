<?php

namespace App\Repository;

use App\Contracts\AnnouncementInterface;
use App\Model\Notice;
use App\Traits\UtilityTrait;
use Carbon\Carbon;

class AnnouncementRepository implements AnnouncementInterface
{
    use UtilityTrait;

    protected $query;

    /**
     * @param $request [limit, filter_date]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($request){
        $limit = $request->has('limit') ? $request->limit : 10;
        $filter_date = $request->has('filter_date') ? ($request->filter_date <> '' ? Carbon::parse($request->filter_date)->toDateString()  : "" ): "";
        $this->query = $filter_date;

        if($request->has('announcementId')){
            $announcements = Notice::where('id','=',$request->announcementId)
                            ->where(function($q){
                            if($this->query <> ''){
                                $q->whereDate('created_at','=',$this->query);
                            }
                       })
                      ->get();
        }else{
            $announcements = Notice::where(function($q){
                            if($this->query <> ''){
                                $q->whereDate('created_at','=',$this->query);
                            }
                       })
                       ->orderByDesc('created_at')->paginate($limit);

        }


        $data = [];
        if(count($announcements) > 0){
            foreach($announcements as $key => $value){
                $item = [
                    'id' => $value->id,
                    'image' => $value->image,
                    'content' => json_encode($value->content),
                    'date' => Carbon::createFromFormat('Y-m-d H:i:s',$value->created_at)->toDateTimeString()
                ];

                array_push($data,$item);
            }
            return static::response(null,static::responseJwtEncoder($data), 201, 'success');
        }
        return static::response('No Data Fetched', null, 200, 'success');
    }

     /**
     * @param $request [email, subject, message]
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitRequest($request){
        #TODO: Implement submitRequest() method
        $email = $request->email;
        $subject = $request->subject;
        $message = $request->message;

        $submit = true;

        if($submit){
            return static::response('Successfully submitted request!',null, 201, 'success');
        }
        return static::response('Failed to submit request!', null, 200, 'success');
    }
}