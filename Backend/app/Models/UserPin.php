<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPin extends Model
{
    protected $table = 'user_pin';
    protected $fillable = ['uid', 'pin', 'last_updated'];
}
