<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'message', 'medium', 'status'];
}
