<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Packages extends Model
{
    protected $primaryKey = 'pid';

    protected $table = 'packages';

    protected $fillable = ['name', 'sid', 'amount', 'medium', 'status'];
}
