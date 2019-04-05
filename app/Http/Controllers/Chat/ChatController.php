<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\ChatInterface;

class ChatController extends Controller
{

    protected $chat, $request;

    public function __construct(ChatInterface $chat, Request $request)
    {
        $this->chat = $chat;
        $this->request = $request;
    }


    /**
     * @SWG\GET(
     *     path="/api/chat/get-users-list",
     *     tags={"CHAT-API"},
     *     summary="Get Chat Users List",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Loaded chat users list!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getChatUsersList()
    {
        return $this->chat->getChatUsersList($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/chat/get-user-chat-list",
     *     tags={"CHAT-API"},
     *     summary="Get User Chat List",
     *     @SWG\Parameter(
     *      name="user_id", in="formData", required=false, type="integer"
     *      ),
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
     *     @SWG\Response(response=200, description="Successfully Loaded user chat list!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserChatList()
    {
        return $this->chat->getUserChatList($this->request);
    }

     /**
     * @SWG\GET(
     *     path="/api/chat/get-all-private-chat/{user_id}",
     *     tags={"CHAT-API"},
     *     summary="Get All private chat",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded all private chat!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getAllPrivate($user_id)
    {
        return $this->chat->getAllPrivate($user_id);
    }

     /**
     * @SWG\GET(
     *     path="/api/chat/get-all-group-chat/{group_id}",
     *     tags={"CHAT-API"},
     *     summary="Get All group chat",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded all group chat!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getAllGroup($group_id)
    {
        return $this->chat->getAllGroup($group_id);
    }

     /**
     * @SWG\GET(
     *     path="/api/chat/get-emoji/{type}",
     *     tags={"CHAT-API"},
     *     summary="Get Emoji by type",
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully loaded emoji!"),
     *     @SWG\Response(response=401, description="Error, No Data Fetched!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getEmojis($type)
    {
        return $this->chat->getEmojis($type);
    }

     /**
     * @SWG\POST(
     *     path="/api/chat/send-to-private-chat",
     *     tags={"CHAT-API"},
     *     summary="Send to private chat",
     *     @SWG\Parameter(
     *      name="msg", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="receiver", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="sender", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully sent private chat!"),
     *     @SWG\Response(response=401, description="Error, Failed to send private chat!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendToPrivate()
    {
        return $this->chat->sendToPrivate($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/chat/send-to-group-chat",
     *     tags={"CHAT-API"},
     *     summary="Send to group chat",
     *     @SWG\Parameter(
     *      name="msg", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="group_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="sender_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully sent group chat!"),
     *     @SWG\Response(response=401, description="Error, Failed to send group chat!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendToGroup()
    {
        return $this->chat->sendToGroup($this->request);
    }

    /**
     * @SWG\POST(
     *     path="/api/chat/create-group-chat",
     *     tags={"CHAT-API"},
     *     summary="Create group chat",
     *     @SWG\Parameter(
     *      name="members", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="group_name", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully created group chat!"),
     *     @SWG\Response(response=401, description="Error, Failed to create group chat!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function createGroupChat()
    {
        return $this->chat->createGroupChat($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/chat/update-group-chat",
     *     tags={"CHAT-API"},
     *     summary="Update group chat",
     *     @SWG\Parameter(
     *      name="group_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="members", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="group_name", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully Updated group chat!"),
     *     @SWG\Response(response=401, description="Error, Failed to create group chat!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function updateGroupChat()
    {
        return $this->chat->updateGroupChat($this->request);
    }

     /**
     * @SWG\POST(
     *     path="/api/chat/leave-group-chat",
     *     tags={"CHAT-API"},
     *     summary="Leave group chat",
     *     @SWG\Parameter(
     *      name="group_id", in="formData", required=true, type="integer"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully leaved group chat!"),
     *     @SWG\Response(response=401, description="Error, Failed to leave group chat!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function leaveGroupChat()
    {
        return $this->chat->leaveGroupChat($this->request);
    }
    
     /**
     * @SWG\POST(
     *     path="/api/chat/save-to-redis",
     *     tags={"CHAT-API"},
     *     summary="Save to Redis",
     *     @SWG\Parameter(
     *      name="key", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="val", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully saved to Redis!"),
     *     @SWG\Response(response=401, description="Error, Failed to save data to redis!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function saveToRedis()
    {
        return $this->chat->saveToRedis($this->request);
    }

    
     /**
     * @SWG\POST(
     *     path="/api/chat/delete-to-redis",
     *     tags={"CHAT-API"},
     *     summary="Delete from Redis",
     *     @SWG\Parameter(
     *      name="key", in="formData", required=true, type="string"
     *      ),
     *     @SWG\Parameter(
     *      name="Authorization", in="header",
     *      required=true,
     *      type="string"
     *      ),
     *     @SWG\Response(response=200, description="Successfully deleted date to Redis!"),
     *     @SWG\Response(response=401, description="Error, Failed to delete data to redis!"),
     *     @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteFromRedis()
    {
        return $this->chat->deleteFromRedis($this->request);
    }
}
