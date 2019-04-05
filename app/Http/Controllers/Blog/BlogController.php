<?php

namespace App\Http\Controllers\Blog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\BlogInterface;

class BlogController extends Controller
{

    protected $blog, $request;

    public function __construct(BlogInterface $blog, Request $request)
    {
        $this->blog = $blog;
        $this->request = $request;
    }


     /**
     * @SWG\POST(
     *     path="/api/landing/blog/get-featured-bloggers",
     *     tags={"LANDING-PAGE-API"},
     *     summary="Get Featured Bloggers",
     *     @SWG\Parameter(
     *      name="limit", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="offset", in="formData", required=false, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded featured bloggers!"),
     *     @SWG\Response(response=401, description="No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getTopBloggers()
    {
       return $this->blog->getTopBloggers($this->request);
    }
}
