<?php

namespace App\Http\Controllers\Social;

use App\Contracts\S;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Manager\SocialInterface;

class SocialController extends Controller
{
    protected $social;
    protected $request;

    public function __construct(SocialInterface $social, Request $request)
    {
        $this->social = $social;
        $this->request = $request;
    }

     /**
     * @SWG\POST(
     *     path="/api/social-connect/hard-unlink",
     *     tags={"SOCIAL-API"},
     *     summary="Hard Unlink",
     *     @SWG\Parameter(
     *      name="sc_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully hard-unlinked account!"),
     *     @SWG\Response(response=401, description="Failed to hard-unlink"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hardUnlink()
    {
        return $this->social->hardUnlink($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/social-connect/hard-unlink-request",
     *     tags={"SOCIAL-API"},
     *     summary="Hard Unlink Request",
     *     @SWG\Parameter(
     *      name="sc_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully submitted hard-unlink request!"),
     *     @SWG\Response(response=401, description="Failed to request hard-unlink"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function hardUnlinkRequest()
    {
        return $this->social->hardUnlinkRequest($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/social-connect/hard-unlink-denied-request",
     *     tags={"SOCIAL-API"},
     *     summary="Denied Hard Unlink Request",
     *     @SWG\Parameter(
     *      name="sc_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="reason", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully denied hard-unlink request!"),
     *     @SWG\Response(response=401, description="Failed to deny hard-unlink request"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deniedHardUnlinkRequest()
    {
        return $this->social->deniedHardUnlinkRequest($this->request);
    }
}
