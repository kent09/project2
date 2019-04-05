<?php

namespace App\Contracts;

interface AnnouncementInterface
{
    public function index($request);

    public function submitRequest($request);
}