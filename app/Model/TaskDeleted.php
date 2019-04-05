<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskDeleted extends Model
{
    protected $table = 'task_deleteds';
}
