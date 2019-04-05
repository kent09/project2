<?php

namespace App\Http\Controllers\Announcement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\AnnouncementInterface;

class AnnouncementController extends Controller
{
    protected $announcement;
    protected $request;

    public function __construct(AnnouncementInterface $announcement, Request $request)
    {
        $this->announcement = $announcement;
        $this->request = $request;
    }

     /**
     * @SWG\POST(
     *     path="/api/announcement",
     *     tags={"ANNOUNCEMENT-API"},
     *     summary="View All Announcements",
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="filter_date", in="formData", required=false, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded announcements!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function index()
    {
        return $this->announcement->index($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/announcement/submit-request",
     *     tags={"ANNOUNCEMENT-API"},
     *     summary="Announcement Submit Request",
     *     @SWG\Parameter(
     *      name="email", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="subject", in="formData", required=false, type="string"
     *      ),
     *    @SWG\Parameter(
     *      name="message", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully submitted request!"),
     *     @SWG\Response(response=401, description="No Data Fetched"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function submitRequest()
    {
        return $this->announcement->submitRequest($this->request);
    }
}
