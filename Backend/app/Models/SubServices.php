<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubServices extends Model
{

    protected $table = 'sub_services';

    public function fetchService()
    {
        return $this->hasOne(Services::class, 'id', 'service_id');
    }

    public function fetchPackages()
    {
        return $this->hasMany(Packages::class, 'sid', 'sid');
    }
}
