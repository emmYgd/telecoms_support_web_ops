<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminPhone extends Model
{
    protected $table = 'admin_phone';
    protected $fillable = ['phone', 'name'];
}
