<?php

namespace App\Contracts\Manager;

interface BankInterface
{

    public function index();

    public function supForApproval($request);

    public function btcForApproval($request);

    public function setSupWithdrawalStatus($data);

    public function setBtcWithdrawalStatus($data);

    public function taskRevokeList($request);

    public function taskCreatorStats($request);

    public function reinstateReward($request);

}