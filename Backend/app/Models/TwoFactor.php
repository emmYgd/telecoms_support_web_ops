<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwoFactor extends Model
{
    protected $table = 'two_factors';

    protected $fillable = ['code', 'uid', 'status'];
}
