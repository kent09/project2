<?php

namespace App\Contracts\Manager;

interface SocialInterface
{

    public function hardUnlink($request);

    public function hardUnlinkRequest($request);

    public function deniedHardUnlinkRequest($request);
}