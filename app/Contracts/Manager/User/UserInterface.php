<?php

namespace App\Contracts\Manager\User;

interface UserInterface
{
    /**
     * Undocumented function
     *
     * @param object $req [paginate: int = 0, page: int = 1]
     * @return json
     */
    public function get_all_users($req);

    /**
     * Undocumented function
     *
     * @param object $req [paginate: int = 0, page: int = 1, status: string]
     * @return json
     */
    public function get_filtered_users($req);

    public function search($req);

    public function user_counts();

    public function getStatistics();

    public function deviceCount();

    public function banUser($req);

    public function unbanUser($req);

    public function accountSummary($username);
    
    public function bannedReasons($user_id);

    public function disableUser($req);

    public function activateUser($req);

    public function setStatusMulti($req);

    public function banUserMulti($req);

    public function unbanUserMulti($req);

    public function countSocialConStatus($req);

    public function socialConnectAll($req);

    public function hardUnlinkRequestList($req);

    public function hardUnlinkedList($req);

    public function softUnlinkedList($req);
}