<?php

namespace App\Contracts;

interface ChatInterface
{

	public function getChatUsersList();

	public function getUserChatList($request);

	public function getAllPrivate($user_id);

	public function getAllGroup($group_id);

	public function getEmojis($type);

	public function sendToPrivate($request);

	public function sendToGroup($request);

	public function createGroupChat($request);

	public function updateGroupChat($request);

	public function leaveGroupChat($request);

	public function saveToRedis($request);

	public function deleteFromRedis($request);
	
}
